<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Actions\BuatTagihanLangganan;
use App\Actions\SelaraskanStatusPembayaran;
use App\Contracts\GerbangPembayaran;
use App\Enums\DurasiPaket;
use App\Enums\SaluranBayar;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\BuatTagihanRequest;
use App\Http\Resources\Pos\LanggananTokoResource;
use App\Http\Resources\Pos\TagihanResource;
use App\Models\Pembayaran;
use App\Support\HargaPaket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

/**
 * Langganan dan tagihannya, dari sisi pemilik toko.
 *
 * Rute-rute di sini sengaja TIDAK dilindungi PastikanLanggananBerjalan —
 * justru pemilik toko yang langganannya sudah habis yang paling butuh membuka
 * halaman ini.
 */
class LanggananController extends Controller
{
    use MilikToko;

    /** @return array<string, mixed> */
    public function show(Request $request): array
    {
        $toko = $this->toko($request);
        $toko->load('langgananBerlaku');

        return [
            'langganan' => new LanggananTokoResource($toko),
            'harga' => $this->daftarHarga($toko->langganan_berakhir_pada !== null),
            'saluran' => $this->daftarSaluran(),
        ];
    }

    /**
     * Saluran yang boleh dipilih — milik server, bukan dikirim klien.
     *
     * @return list<array{kode: string, label: string, pakaiKode: bool}>
     */
    private function daftarSaluran(): array
    {
        return array_map(static fn (SaluranBayar $s): array => [
            'kode' => $s->value,
            'label' => $s->label(),
            'pakaiKode' => $s->pakaiKode(),
        ], SaluranBayar::daftar());
    }

    public function riwayatTagihan(Request $request): AnonymousResourceCollection
    {
        return TagihanResource::collection(
            Pembayaran::query()
                ->where('pos_user_id', $this->toko($request)->id)
                ->orderByDesc('tanggal')
                ->orderByDesc('id')
                ->limit(50)
                ->get(),
        );
    }

    public function buatTagihan(BuatTagihanRequest $request, BuatTagihanLangganan $buat): TagihanResource
    {
        return new TagihanResource($buat(
            toko: $this->toko($request),
            durasi: $request->durasi(),
            saluran: $request->saluran(),
        ));
    }

    public function tagihan(Request $request, Pembayaran $pembayaran): TagihanResource
    {
        $this->pastikanMilikToko($request, $pembayaran->pos_user_id);

        return new TagihanResource($pembayaran);
    }

    /**
     * Tombol "Saya sudah bayar".
     *
     * Bukan jalur utama pelunasan — itu webhook. Ini jaring pengaman untuk
     * pengguna yang sudah membayar tapi notifikasinya belum sampai, dan untuk
     * jaringan yang memutus notifikasi di tengah jalan.
     */
    public function periksaTagihan(
        Request $request,
        Pembayaran $pembayaran,
        GerbangPembayaran $gerbang,
        SelaraskanStatusPembayaran $selaraskan,
    ): TagihanResource {
        $this->pastikanMilikToko($request, $pembayaran->pos_user_id);

        if ($pembayaran->status->final()) {
            return new TagihanResource($pembayaran);
        }

        $status = $gerbang->periksaStatus($pembayaran);

        /*
         * Gerbang yang sama dengan webhook: hanya `paid` dari transaksi detail
         * yang melunasi. Status `paid` dengan nominal tidak cocok ditahan —
         * sesuatu di sisi Mayar salah konfigurasi, dan melunasi atas dasar itu
         * berarti menutupinya. Status lain (expired, closed, ...) diteruskan
         * ke penelaras biar tersimpan, tapi tidak pernah melunasi.
         */
        if (($status['transaction_status'] ?? '') === 'paid'
            && (int) ($status['gross_amount'] ?? 0) !== $pembayaran->nominal) {
            Log::warning('Nominal transaksi Mayar tidak cocok saat diperiksa.', [
                'invoice' => $pembayaran->nomor_invoice,
                'diharapkan' => $pembayaran->nominal,
                'diterima' => $status['gross_amount'] ?? null,
            ]);

            return new TagihanResource($pembayaran->refresh());
        }

        return new TagihanResource($selaraskan($pembayaran, $status));
    }

    /**
     * Harga tiap durasi, lengkap dengan hemat dan tanggal berlaku barunya.
     *
     * Dihitung di server supaya klaim "hemat 25%" tidak pernah jadi angka
     * pemasaran yang ditulis tangan lalu basi begitu harga diubah dari panel.
     *
     * @return list<array<string, mixed>>
     */
    private function daftarHarga(bool $menyambung): array
    {
        $bulanan = HargaPaket::untuk(DurasiPaket::Bulanan);

        return array_map(static function (DurasiPaket $durasi) use ($bulanan): array {
            $harga = HargaPaket::untuk($durasi);
            $penuh = $bulanan * $durasi->bulan();

            return [
                'durasi' => $durasi->value,
                'label' => $durasi->label(),
                'bulan' => $durasi->bulan(),
                'harga' => $harga,
                'perBulan' => $durasi->bulan() === 0 ? 0 : intdiv($harga, $durasi->bulan()),
                'hematPersen' => $durasi->bulan() <= 1 || $penuh === 0
                    ? 0
                    : (int) round((($penuh - $harga) * 100) / $penuh),
            ];
        }, DurasiPaket::berbayar());
    }
}
