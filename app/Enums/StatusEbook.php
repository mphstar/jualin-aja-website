<?php

declare(strict_types=1);

namespace App\Enums;

/** Hanya ebook berstatus Terbit yang terlihat oleh pemilik toko (PRD §F5.5). */
enum StatusEbook: string
{
    case Draf = 'DRAF';
    case Terbit = 'TERBIT';

    public function label(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Terbit => 'Terbit',
        };
    }
}
