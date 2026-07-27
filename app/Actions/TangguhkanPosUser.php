<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\JenisAksi;
use App\Enums\TargetAksi;
use App\Models\PosUser;
use App\Services\PencatatAktivitas;

/**
 * Menangguhkan akun pemilik toko (PRD §F3.7). Alasan wajib dan ikut tercatat
 * di log — tanpa itu, tidak ada cara mengetahui kenapa sebuah akun mati.
 */
final readonly class TangguhkanPosUser
{
    public function __construct(private PencatatAktivitas $pencatat) {}

    public function __invoke(PosUser $posUser, string $alasan): PosUser
    {
        $posUser->update([
            'ditangguhkan' => true,
            'alasan_penangguhan' => $alasan,
        ]);

        $this->pencatat->catat(
            aksi: JenisAksi::UserTangguhkan,
            targetTipe: TargetAksi::User,
            deskripsi: sprintf('Menangguhkan akun %s. Alasan: %s', $posUser->nama_toko, $alasan),
            targetId: (string) $posUser->id,
            targetLabel: $posUser->nama_toko,
        );

        return $posUser;
    }
}
