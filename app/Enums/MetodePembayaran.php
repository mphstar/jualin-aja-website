<?php

declare(strict_types=1);

namespace App\Enums;

enum MetodePembayaran: string
{
    case TransferBank = 'TRANSFER_BANK';
    case Qris = 'QRIS';
    case VirtualAccount = 'VIRTUAL_ACCOUNT';
    case Ewallet = 'EWALLET';
    case Manual = 'MANUAL';

    public function label(): string
    {
        return match ($this) {
            self::TransferBank => 'Transfer Bank',
            self::Qris => 'QRIS',
            self::VirtualAccount => 'Virtual Account',
            self::Ewallet => 'E-Wallet',
            self::Manual => 'Manual / Tunai',
        };
    }
}
