<?php

declare(strict_types=1);

namespace App\Enums;

/** Enum tetap — tidak ada CRUD kategori (PRD §F5.3). */
enum KategoriEbook: string
{
    case Minuman = 'MINUMAN';
    case MakananBerat = 'MAKANAN_BERAT';
    case Snack = 'SNACK';
    case Dessert = 'DESSERT';
    case Bakery = 'BAKERY';
    case BumbuSaus = 'BUMBU_SAUS';

    public function label(): string
    {
        return match ($this) {
            self::Minuman => 'Minuman',
            self::MakananBerat => 'Makanan Berat',
            self::Snack => 'Snack',
            self::Dessert => 'Dessert',
            self::Bakery => 'Bakery',
            self::BumbuSaus => 'Bumbu & Saus',
        };
    }
}
