<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cara pembeli membayar di kasir toko.
 *
 * Sengaja BUKAN App\Enums\MetodePembayaran — yang itu cara pemilik toko
 * membayar langganannya ke platform. Dua uang yang berbeda arah, dan
 * menggabungkannya membuat laporan omzet toko bercampur dengan pendapatan
 * platform.
 */
enum MetodeBayarPos: string
{
    case Tunai = 'TUNAI';
    case Qris = 'QRIS';
    case Transfer = 'TRANSFER';

    public function label(): string
    {
        return match ($this) {
            self::Tunai => 'Tunai',
            self::Qris => 'QRIS',
            self::Transfer => 'Transfer',
        };
    }
}
