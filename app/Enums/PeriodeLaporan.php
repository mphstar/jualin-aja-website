<?php

declare(strict_types=1);

namespace App\Enums;

/** Rentang yang bisa dipilih di layar Laporan aplikasi POS. */
enum PeriodeLaporan: string
{
    case HariIni = 'HARI_INI';
    case TujuhHari = 'TUJUH_HARI';
    case TigaPuluhHari = 'TIGA_PULUH_HARI';

    public function label(): string
    {
        return match ($this) {
            self::HariIni => 'Hari ini',
            self::TujuhHari => '7 hari',
            self::TigaPuluhHari => '30 hari',
        };
    }

    /** Termasuk hari ini, jadi `HariIni` bernilai 1 — bukan 0. */
    public function hari(): int
    {
        return match ($this) {
            self::HariIni => 1,
            self::TujuhHari => 7,
            self::TigaPuluhHari => 30,
        };
    }
}
