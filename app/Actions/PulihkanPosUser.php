<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\JenisAksi;
use App\Enums\TargetAksi;
use App\Models\PosUser;
use App\Services\PencatatAktivitas;

final readonly class PulihkanPosUser
{
    public function __construct(private PencatatAktivitas $pencatat) {}

    public function __invoke(PosUser $posUser): PosUser
    {
        $posUser->update([
            'ditangguhkan' => false,
            'alasan_penangguhan' => null,
        ]);

        $this->pencatat->catat(
            aksi: JenisAksi::UserPulihkan,
            targetTipe: TargetAksi::User,
            deskripsi: sprintf('Memulihkan akun %s.', $posUser->nama_toko),
            targetId: (string) $posUser->id,
            targetLabel: $posUser->nama_toko,
        );

        return $posUser;
    }
}
