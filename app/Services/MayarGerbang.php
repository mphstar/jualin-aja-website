<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GerbangPembayaran;
use App\Enums\SaluranBayar;
use App\Exceptions\KesalahanDomain;
use App\Models\Pembayaran;
use App\Support\HasilCharge;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mayar lewat API V2 — native checkout.
 *
 * Pembeli TIDAK dibawa keluar: backend membuat invoice dengan `paymentMethod`
 * yang dikunci, Mayar menjawab `paymentDetail`, dan aplikasi menggambar
 * instrumennya sendiri (kode QR sekarang; VA/e-wallet begitu salurannya
 * dibuka). Halaman hosted cuma jadi cadangan ketika `paymentDetail` tidak
 * dikenal.
 *
 * Dipanggil langsung lewat HTTP client Laravel, tanpa SDK. Yang dipakai hanya
 * dua endpoint; menambah dependensi untuk itu berarti menambah sesuatu yang
 * harus ikut diperbarui setiap kali ada CVE di pohon dependensinya.
 *
 * @see https://docs.mayar.id/api-reference-v2/invoice/create.md
 * @see https://docs.mayar.id/api-reference-v2/transaction/detail.md
 */
final readonly class MayarGerbang implements GerbangPembayaran
{
    /** Umur invoice untuk QRIS — pendek, sesuai umur hidup kode QR. */
    public const int JAM_KEDALUWARSA = 1;

    public function __construct(
        private ?string $apiKey,
        private bool $produksi,
        private int $timeout = 15,
    ) {}

    public function buatTransaksi(Pembayaran $pembayaran, SaluranBayar $saluran): HasilCharge
    {
        $pembayaran->loadMissing('posUser');
        $toko = $pembayaran->posUser;

        $batasBayar = CarbonImmutable::now()->addHours(self::JAM_KEDALUWARSA);

        $badan = [
            'name' => trim($toko->nama),
            'email' => $toko->email,
            'mobile' => $toko->telepon,
            'items' => [[
                'quantity' => 1,
                'rate' => (int) $pembayaran->nominal,
                'description' => mb_substr('Langganan Jualin Aja '.$pembayaran->durasi->label(), 0, 255),
            ]],
            'description' => mb_substr('Invoice '.$pembayaran->nomor_invoice, 0, 255),
            'expiredAt' => $batasBayar->toIso8601String(),
            'paymentMethod' => $saluran->value,
            // Id pesanan kita sendiri — dibaca ulang dari transaksi, bukan dari
            // webhook, untuk membuktikan satu pembayaran milik satu invoice.
            'extraData' => ['orderId' => $pembayaran->nomor_invoice],
        ];

        $jawaban = $this->kirim(fn (PendingRequest $http): Response => $http->post(
            $this->basisApi().'/hl/v2/invoices/create',
            $badan,
        ));

        $data = is_array($jawaban['data'] ?? null) ? $jawaban['data'] : [];

        if (($jawaban['statusCode'] ?? null) !== 200) {
            $pesan = (string) ($jawaban['messages'] ?? 'gerbang pembayaran menolak.');

            if (($jawaban['statusCode'] ?? null) === 401) {
                $pesan .= ' — periksa API key Mayar dan mode sandbox/produksinya.';
            }

            if (($jawaban['statusCode'] ?? null) === 429) {
                $pesan .= ' Coba lagi beberapa saat.';
            }

            throw new KesalahanDomain('Gagal membuat tagihan: '.$pesan);
        }

        $instruksi = $this->parsePaymentDetail($data['paymentDetail'] ?? null);
        $kedaluwarsaSaluran = $this->kedaluwarsaSaluran($data['paymentDetail'] ?? null);

        return new HasilCharge(
            transactionId: (string) ($data['transactionId'] ?? ''),
            orderId: (string) ($data['id'] ?? ''),
            payload: $jawaban,
            batasBayar: $kedaluwarsaSaluran ?? $batasBayar,
            kedaluwarsaSaluran: $kedaluwarsaSaluran,
            qrUrl: $instruksi['qrUrl'] ?? null,
            tautanBayar: (string) ($data['link'] ?? ''),
            instruksi: $instruksi,
        );
    }

    public function periksaStatus(Pembayaran $pembayaran): array
    {
        $transactionId = (string) $pembayaran->mayar_transaction_id;

        if ($transactionId === '') {
            return ['transaction_status' => '', 'data' => []];
        }

        $jawaban = $this->kirim(fn (PendingRequest $http): Response => $http->get(
            sprintf('%s/hl/v2/transactions/%s', $this->basisApi(), rawurlencode($transactionId)),
        ));

        return $this->normalkan($jawaban);
    }

    /**
     * Verifikasi bentuk notifikasi webhook.
     *
     * Mayar belum menandatangani webhooknya — tidak ada `signature_key`. Bentuk
     * yang diuji di sini hanyalah peluru pertama; yang mempertegas keasliannya
     * adalah bahwa `transactionId` itu memang dibuat kita dan statusnya dibaca
     * ulang dari `GET /transactions/{id}`, bukan dari isi webhook.
     *
     * @param  array<string, mixed>  $payload
     */
    public function notifikasiSah(array $payload): bool
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        $id = (string) ($data['transactionId'] ?? $data['id'] ?? '');

        return is_string($payload['event'] ?? null)
            && $payload['event'] !== ''
            && $data !== []
            && $id !== '';
    }

    /**
     * Ubah `paymentDetail` yang tidak terdokumentasi jadi instruksi kita.
     *
     * `paymentDetail` dijawab create saja dan tidak didefinisikan dokumen apa
     * pun — perlakukan sebagai masukan tak tepercaya. Bentuk yang tidak dikenal
     * mengembalikan null, dan layar jatuh ke tautan hosted.
     *
     * @return array<string, mixed>|null
     */
    private function parsePaymentDetail(mixed $paymentDetail): ?array
    {
        $detail = is_array($paymentDetail) ? $paymentDetail : [];

        $tipe = strtoupper((string) ($detail['type'] ?? ''));

        if ($tipe === 'QR_CODE') {
            $kelompok = $detail['qr_code'] ?? null;
            $properti = is_array($kelompok) ? ($kelompok['channel_properties'] ?? []) : [];
            $qr = is_array($properti) ? (string) ($properti['qr_string'] ?? '') : '';

            if ($qr === '') {
                Log::warning('paymentDetail QR_CODE tanpa qr_string.', ['badan' => $detail]);

                return null;
            }

            return [
                'tipe' => 'qr_code',
                'qrUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data='.rawurlencode($qr),
                'kodeBayar' => null,
                'kodePerusahaan' => null,
                'aksi' => [],
            ];
        }

        if ($tipe !== '') {
            Log::warning('paymentDetail dengan tipe tak dikenal.', ['tipe' => $tipe]);
        }

        return null;
    }

    /**
     * Kedaluwarsa saluran — menang atas kedaluwarsa invoice.
     *
     * Kode QR berhenti bekerja pada waktunya sendiri berapa pun kata invoice.
     * Kalau tidak terlihat, gunakan kedaluwarsa invoice.
     */
    private function kedaluwarsaSaluran(mixed $paymentDetail): ?CarbonImmutable
    {
        $detail = is_array($paymentDetail) ? $paymentDetail : [];
        $kelompok = $detail['qr_code'] ?? null;
        $properti = is_array($kelompok) ? ($kelompok['channel_properties'] ?? []) : [];
        $berakhir = is_array($properti) ? ($properti['expires_at'] ?? null) : null;

        return $this->bacaWaktu($berakhir);
    }

    private function bacaWaktu(mixed $nilai): ?CarbonImmutable
    {
        if (is_numeric($nilai)) {
            return CarbonImmutable::createFromTimestampMs((int) $nilai);
        }

        if (is_string($nilai) && $nilai !== '') {
            try {
                return CarbonImmutable::parse($nilai);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * Bentuk payload yang dipahami `SelaraskanStatusPembayaran`.
     *
     * @param  array<string, mixed>  $jawaban
     * @return array<string, mixed>
     */
    private function normalkan(array $jawaban): array
    {
        $data = is_array($jawaban['data'] ?? null) ? $jawaban['data'] : [];

        return [
            'transaction_status' => (string) ($data['status'] ?? ''),
            'transaction_id' => (string) ($data['id'] ?? ''),
            'gross_amount' => (int) ($data['amount'] ?? 0),
            'payment_method' => $data['paymentMethod'] ?? null,
            'extraData' => $data['extraData'] ?? null,
            'data' => $data,
        ];
    }

    /**
     * @param  callable(PendingRequest): Response  $panggil
     * @return array<string, mixed>
     */
    private function kirim(callable $panggil): array
    {
        if (($this->apiKey ?? '') === '') {
            throw new KesalahanDomain(
                'Pembayaran otomatis belum aktif. Hubungi dukungan untuk perpanjangan manual.',
                503,
            );
        }

        try {
            $respons = $panggil(
                Http::withToken($this->apiKey)
                    ->acceptJson()
                    ->asJson()
                    ->timeout($this->timeout)
                    // Dua kali coba lagi dengan jeda: kegagalan jaringan
                    // sesaat tidak boleh terbaca sebagai penolakan gerbang di
                    // layar pengguna.
                    ->retry(2, 200, throw: false),
            );
        } catch (ConnectionException $e) {
            Log::warning('Mayar tidak bisa dihubungi.', ['pesan' => $e->getMessage()]);

            throw new KesalahanDomain('Gerbang pembayaran sedang tidak bisa dihubungi. Coba lagi.', 503);
        }

        /** @var array<string, mixed> $badan */
        $badan = $respons->json() ?? [];

        if (! $respons->successful()) {
            Log::warning('Mayar menolak permintaan.', [
                'basis' => $this->basisApi(),
                'status' => $respons->status(),
                'status_code' => $badan['statusCode'] ?? null,
                'messages' => $badan['messages'] ?? null,
                'badan' => $badan,
            ]);
        }

        if ($respons->serverError() || $badan === []) {
            Log::error('Mayar menjawab tidak terduga.', [
                'status' => $respons->status(),
                'badan' => $respons->body(),
            ]);

            throw new KesalahanDomain('Gerbang pembayaran sedang bermasalah. Coba lagi nanti.', 503);
        }

        return $badan;
    }

    private function basisApi(): string
    {
        return $this->produksi
            ? 'https://api.mayar.id'
            : 'https://api.mayar.io';
    }
}
