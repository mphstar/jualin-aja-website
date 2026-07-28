<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\JenisAksi;
use App\Enums\StatusPembayaran;
use App\Enums\TargetAksi;
use App\Exceptions\KesalahanDomain;
use App\Models\Pembayaran;
use App\Services\PencatatAktivitas;

final readonly class TandaiPembayaranGagal
{
    public function __construct(private PencatatAktivitas $pencatat) {}

    /**
     * @param  StatusPembayaran  $status  Gagal (ditolak) atau Kedaluwarsa
     *                                    (batas waktunya lewat tanpa dibayar).
     */
    public function __invoke(
        Pembayaran $pembayaran,
        StatusPembayaran $status = StatusPembayaran::Gagal,
    ): Pembayaran {
        if ($pembayaran->status === StatusPembayaran::Lunas) {
            // Membatalkan invoice yang sudah lunas berarti langganannya juga
            // harus ditarik kembali. Selama alur itu belum ada, jalan ini ditutup
            // supaya data pembayaran dan masa aktif tidak pernah bertentangan.
            throw new KesalahanDomain('Invoice yang sudah lunas tidak bisa ditandai gagal.');
        }

        if ($status !== StatusPembayaran::Gagal && $status !== StatusPembayaran::Kedaluwarsa) {
            throw new KesalahanDomain('Status akhir yang diminta tidak dikenali.');
        }

        $pembayaran->update(['status' => $status]);

        $this->pencatat->catat(
            aksi: JenisAksi::PembayaranGagal,
            targetTipe: TargetAksi::Pembayaran,
            deskripsi: sprintf(
                'Menandai invoice %s sebagai %s.',
                $pembayaran->nomor_invoice,
                mb_strtolower($status->label()),
            ),
            targetId: (string) $pembayaran->id,
            targetLabel: $pembayaran->nomor_invoice,
        );

        return $pembayaran;
    }
}
