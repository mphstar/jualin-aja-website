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
 * Midtrans lewat Core API.
 *
 * **Core API, bukan Snap.** Snap menampilkan halaman pembayaran milik Midtrans
 * di dalam WebView; aplikasi ini justru menggambar instruksinya sendiri —
 * nomor VA yang bisa disalin sekali ketuk, batas waktu sebagai tanggal, tombol
 * "Saya sudah bayar" di tempat yang sama saat pengguna kembali. Core API
 * mengembalikan bahan mentahnya, jadi tampilannya tidak menumpang gaya orang
 * lain di tengah aplikasi.
 *
 * Dipanggil langsung lewat HTTP client Laravel, tanpa SDK. Yang dipakai hanya
 * tiga endpoint dan satu SHA-512; menambah dependensi untuk itu berarti
 * menambah sesuatu yang harus ikut diperbarui setiap kali ada CVE di pohon
 * dependensinya.
 *
 * @see https://docs.midtrans.com/reference/charge-transactions
 */
final readonly class MidtransGerbang implements GerbangPembayaran
{
    /** Midtrans menutup tagihan yang tidak dibayar. 24 jam lazim dan cukup
     *  longgar untuk orang yang membayar lewat ATM besok pagi. */
    public const int JAM_KEDALUWARSA = 24;

    public function __construct(
        private ?string $serverKey,
        private bool $produksi,
        private int $timeout = 15,
    ) {}

    public function buatTransaksi(Pembayaran $pembayaran, SaluranBayar $saluran): HasilCharge
    {
        $pembayaran->loadMissing('posUser');
        $toko = $pembayaran->posUser;

        $batasBayar = CarbonImmutable::now()->addHours(self::JAM_KEDALUWARSA);

        $badan = [
            'payment_type' => $saluran->tipePembayaranMidtrans(),
            'transaction_details' => [
                'order_id' => $pembayaran->midtrans_order_id ?? $pembayaran->nomor_invoice,
                // Midtrans menuntut bilangan bulat rupiah. Seluruh nominal di
                // domain ini memang tidak pernah punya pecahan.
                'gross_amount' => $pembayaran->nominal,
            ],
            'item_details' => [[
                'id' => $pembayaran->durasi->value,
                'price' => $pembayaran->nominal,
                'quantity' => 1,
                'name' => 'Langganan Jualin Aja '.$pembayaran->durasi->label(),
            ]],
            'customer_details' => [
                'first_name' => $toko->nama,
                'email' => $toko->email,
                'phone' => $toko->telepon,
            ],
            'custom_expiry' => [
                'expiry_duration' => self::JAM_KEDALUWARSA,
                'unit' => 'hour',
            ],
            ...$this->rincianSaluran($saluran, $pembayaran),
        ];

        $jawaban = $this->kirim(fn (PendingRequest $http): Response => $http->post(
            $this->basisApi().'/v2/charge',
            $badan,
        ));

        /*
         * 201 = transaksi dibuat dan menunggu pembayaran. 200 juga sukses
         * (langsung settlement, terjadi pada saluran tertentu). Selain itu
         * Midtrans menolak, dan pesannya lebih berguna bagi pengguna daripada
         * "terjadi kesalahan".
         */
        $kode = (string) ($jawaban['status_code'] ?? '');

        if (! in_array($kode, ['200', '201'], true)) {
            throw new KesalahanDomain(
                'Gagal membuat tagihan: '.($jawaban['status_message'] ?? 'gerbang pembayaran menolak.'),
            );
        }

        return new HasilCharge(
            transactionId: (string) ($jawaban['transaction_id'] ?? ''),
            orderId: (string) ($jawaban['order_id'] ?? ''),
            payload: $jawaban,
            batasBayar: isset($jawaban['expiry_time'])
                ? CarbonImmutable::parse((string) $jawaban['expiry_time'])
                : $batasBayar,
            kodeBayar: $this->kodeBayar($saluran, $jawaban),
            kodePerusahaan: $saluran === SaluranBayar::VaMandiri
                ? ($jawaban['biller_code'] ?? null)
                : null,
            qrUrl: $this->aksi($jawaban, 'generate-qr-code'),
            tautanBayar: $this->aksi($jawaban, 'deeplink-redirect'),
        );
    }

    public function periksaStatus(Pembayaran $pembayaran): array
    {
        $orderId = $pembayaran->midtrans_order_id ?? $pembayaran->nomor_invoice;

        return $this->kirim(fn (PendingRequest $http): Response => $http->get(
            sprintf('%s/v2/%s/status', $this->basisApi(), rawurlencode($orderId)),
        ));
    }

    /**
     * Verifikasi tanda tangan notifikasi.
     *
     * `sha512(order_id + status_code + gross_amount + server_key)`. Tanpa ini,
     * siapa pun yang tahu alamat webhook bisa mengirim "settlement" dan
     * memperpanjang langganan gratis — endpoint webhook memang harus terbuka
     * untuk publik, jadi tanda tangan inilah satu-satunya yang membedakan
     * Midtrans dari orang lain.
     *
     * Dibandingkan dengan `hash_equals`, bukan `===`: perbandingan string biasa
     * berhenti di karakter pertama yang berbeda, dan selisih waktunya cukup
     * untuk menebak tanda tangan satu karakter demi satu karakter.
     */
    public function notifikasiSah(array $payload): bool
    {
        $tandaTangan = (string) ($payload['signature_key'] ?? '');

        if ($tandaTangan === '' || ($this->serverKey ?? '') === '') {
            return false;
        }

        $bahan = (string) ($payload['order_id'] ?? '')
            .(string) ($payload['status_code'] ?? '')
            .(string) ($payload['gross_amount'] ?? '')
            .$this->serverKey;

        return hash_equals(hash('sha512', $bahan), $tandaTangan);
    }

    /**
     * Bagian badan permintaan yang khas per saluran.
     *
     * @return array<string, mixed>
     */
    private function rincianSaluran(SaluranBayar $saluran, Pembayaran $pembayaran): array
    {
        return match ($saluran) {
            SaluranBayar::Qris => ['qris' => ['acquirer' => 'gopay']],
            SaluranBayar::VaBca => ['bank_transfer' => ['bank' => 'bca']],
            SaluranBayar::VaMandiri => ['echannel' => [
                'bill_info1' => 'Langganan',
                'bill_info2' => $pembayaran->nomor_invoice,
            ]],
            SaluranBayar::Gopay => ['gopay' => ['enable_callback' => false]],
        };
    }

    /**
     * Nomor yang harus dimasukkan pembayar di aplikasi banknya.
     *
     * @param  array<string, mixed>  $jawaban
     */
    private function kodeBayar(SaluranBayar $saluran, array $jawaban): ?string
    {
        return match ($saluran) {
            SaluranBayar::VaBca => $jawaban['va_numbers'][0]['va_number'] ?? null,
            SaluranBayar::VaMandiri => $jawaban['bill_key'] ?? null,
            default => null,
        };
    }

    /**
     * Ambil URL dari daftar `actions` berdasarkan namanya.
     *
     * @param  array<string, mixed>  $jawaban
     */
    private function aksi(array $jawaban, string $nama): ?string
    {
        foreach ((array) ($jawaban['actions'] ?? []) as $aksi) {
            if (is_array($aksi) && ($aksi['name'] ?? null) === $nama) {
                return (string) $aksi['url'];
            }
        }

        return null;
    }

    /**
     * @param  callable(PendingRequest): Response  $panggil
     * @return array<string, mixed>
     */
    private function kirim(callable $panggil): array
    {
        if (($this->serverKey ?? '') === '') {
            throw new KesalahanDomain(
                'Pembayaran otomatis belum aktif. Hubungi dukungan untuk perpanjangan manual.',
                503,
            );
        }

        try {
            $respons = $panggil(
                Http::withBasicAuth($this->serverKey, '')
                    ->acceptJson()
                    ->asJson()
                    ->timeout($this->timeout)
                    // Dua kali coba lagi dengan jeda: kegagalan jaringan
                    // sesaat tidak boleh terbaca sebagai penolakan gerbang di
                    // layar pengguna.
                    ->retry(2, 200, throw: false),
            );
        } catch (ConnectionException $e) {
            Log::warning('Midtrans tidak bisa dihubungi.', ['pesan' => $e->getMessage()]);

            throw new KesalahanDomain('Gerbang pembayaran sedang tidak bisa dihubungi. Coba lagi.', 503);
        }

        /** @var array<string, mixed> $badan */
        $badan = $respons->json() ?? [];

        /*
         * Midtrans menjawab 4xx untuk transaksi yang ditolak, tapi badannya
         * tetap berisi `status_message` yang layak dibaca manusia. Yang benar-
         * benar tidak punya badan (5xx, HTML error page) yang dilempar di sini.
         */
        if ($respons->serverError() || $badan === []) {
            Log::error('Midtrans menjawab tidak terduga.', [
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
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }
}
