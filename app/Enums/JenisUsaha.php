<?php

declare(strict_types=1);

namespace App\Enums;

enum JenisUsaha: string
{
    case Kafe = 'KAFE';
    case Restoran = 'RESTORAN';
    case WarungMakan = 'WARUNG_MAKAN';
    case Bakery = 'BAKERY';
    case TokoKelontong = 'TOKO_KELONTONG';
    case Lainnya = 'LAINNYA';

    public function label(): string
    {
        return match ($this) {
            self::Kafe => 'Kafe',
            self::Restoran => 'Restoran',
            self::WarungMakan => 'Warung Makan',
            self::Bakery => 'Bakery',
            self::TokoKelontong => 'Toko Kelontong',
            self::Lainnya => 'Lainnya',
        };
    }
}
