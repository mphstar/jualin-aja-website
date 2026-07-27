<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status langganan TIDAK PERNAH disimpan di database — selalu diturunkan
 * dari tanggal berakhir + flag ditangguhkan, supaya tidak bisa basi
 * (PRD §4.2). Perhitungannya ada di App\Support\KondisiLangganan.
 */
enum StatusLangganan: string
{
    case Trial = 'TRIAL';
    case Aktif = 'AKTIF';
    case AkanBerakhir = 'AKAN_BERAKHIR';
    case Kedaluwarsa = 'KEDALUWARSA';
    case Nonaktif = 'NONAKTIF';

    public function label(): string
    {
        return match ($this) {
            self::Trial => 'Uji Coba',
            self::Aktif => 'Aktif',
            self::AkanBerakhir => 'Akan Berakhir',
            self::Kedaluwarsa => 'Kedaluwarsa',
            self::Nonaktif => 'Nonaktif',
        };
    }

    /** Apakah pemilik status ini boleh mengunduh ebook? (PRD §4.3) */
    public function bolehUnduhEbook(): bool
    {
        return in_array($this, [self::Aktif, self::AkanBerakhir, self::Trial], true);
    }
}
