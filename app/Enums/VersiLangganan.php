<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Versi / Tier paket langganan (Gratis, Trial, Langganan).
 */
enum VersiLangganan: string
{
    case Gratis = 'GRATIS';
    case Trial = 'TRIAL';
    case Langganan = 'LANGGANAN';

    public function label(): string
    {
        return match ($this) {
            self::Gratis => 'Gratis',
            self::Trial => 'Trial (Uji Coba)',
            self::Langganan => 'Langganan (Aktif)',
        };
    }
}
