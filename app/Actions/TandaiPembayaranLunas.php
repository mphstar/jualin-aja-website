<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DurasiPaket;
use App\Enums\JenisAksi;
use App\Enums\StatusPembayaran;
use App\Enums\SumberLangganan;
use App\Enums\TargetAksi;
use App\Exceptions\KesalahanDomain;
use App\Models\Pembayaran;
use App\Services\PencatatAktivitas;
use Illuminate\Support\Facades\DB;

/**
 * Tandai invoice lunas — sekaligus memperpanjang langganan tokonya (PRD §F6.7).
 *
 * Ini titik masuk TUNGGAL pelunasan. Webhook Mayar memanggil action
 * yang sama, jadi perpanjangan langganan dan pencatatan log ikut terjadi tanpa
 * ada aturan yang perlu digandakan.
 */
final readonly class TandaiPembayaranLunas
{
    public function __construct(
        private PencatatAktivitas $pencatat,
        private PerpanjangLangganan $perpanjangLangganan,
    ) {}

    public function __invoke(Pembayaran $pembayaran): Pembayaran
    {
        if ($pembayaran->status === StatusPembayaran::Lunas) {
            throw new KesalahanDomain('Invoice ini sudah berstatus lunas.');
        }

        return DB::transaction(function () use ($pembayaran): Pembayaran {
            $pembayaran->update([
                'status' => StatusPembayaran::Lunas,
                'dibayar_pada' => now(),
            ]);

            $this->pencatat->catat(
                aksi: JenisAksi::PembayaranLunas,
                targetTipe: TargetAksi::Pembayaran,
                deskripsi: sprintf('Menandai invoice %s sebagai lunas.', $pembayaran->nomor_invoice),
                targetId: (string) $pembayaran->id,
                targetLabel: $pembayaran->nomor_invoice,
            );

            if ($pembayaran->durasi !== DurasiPaket::Trial) {
                $pembayaran->loadMissing('posUser');

                $langganan = ($this->perpanjangLangganan)(
                    posUser: $pembayaran->posUser,
                    durasi: $pembayaran->durasi,
                    catatan: sprintf('Otomatis dari pelunasan invoice %s.', $pembayaran->nomor_invoice),
                    // Sumbernya PEMBELIAN, bukan PERPANJANGAN_MANUAL: siklus ini
                    // lahir dari transaksi yang dibayar, bukan dari tangan admin.
                    sumber: SumberLangganan::Pembelian,
                );

                $pembayaran->update(['langganan_id' => $langganan->id]);
            }

            return $pembayaran;
        });
    }
}
