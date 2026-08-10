<?php

declare(strict_types=1);

namespace App\Enums;

enum PrioritasTiket: string
{
    case Rendah = 'RENDAH';
    case Sedang = 'SEDANG';
    case Tinggi = 'TINGGI';

    public function label(): string
    {
        return match ($this) {
            self::Rendah => 'Rendah',
            self::Sedang => 'Sedang',
            self::Tinggi => 'Tinggi',
        };
    }
}
