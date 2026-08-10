<?php

declare(strict_types=1);

namespace App\Enums;

enum JenisTiket: string
{
    case Saran = 'SARAN';
    case Komplain = 'KOMPLAIN';
    case Pertanyaan = 'PERTANYAAN';

    public function label(): string
    {
        return match ($this) {
            self::Saran => 'Saran Pengembangan',
            self::Komplain => 'Komplain / Laporan Bug',
            self::Pertanyaan => 'Pertanyaan Bantuan',
        };
    }
}
