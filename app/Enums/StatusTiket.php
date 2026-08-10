<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusTiket: string
{
    case Terbuka = 'TERBUKA';
    case Diproses = 'DIPROSES';
    case Selesai = 'SELESAI';
    case Ditutup = 'DITUTUP';

    public function label(): string
    {
        return match ($this) {
            self::Terbuka => 'Terbuka',
            self::Diproses => 'Sedang Diproses',
            self::Selesai => 'Selesai',
            self::Ditutup => 'Ditutup',
        };
    }
}
