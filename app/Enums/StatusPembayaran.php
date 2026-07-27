<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusPembayaran: string
{
    case Lunas = 'LUNAS';
    case Menunggu = 'MENUNGGU';
    case Gagal = 'GAGAL';
    case Refund = 'REFUND';

    public function label(): string
    {
        return match ($this) {
            self::Lunas => 'Lunas',
            self::Menunggu => 'Menunggu',
            self::Gagal => 'Gagal',
            self::Refund => 'Refund',
        };
    }
}
