<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Satu paket, beda durasi (PRD §4.1). Fitur identik di semua durasi —
 * yang membedakan hanya lama berlangganan dan harga.
 */
enum DurasiPaket: string
{
    case Trial = 'TRIAL';
    case Bulanan = 'BULANAN';
    case Semesteran = 'SEMESTERAN';
    case Tahunan = 'TAHUNAN';

    /**
     * Berapa bulan yang ditambahkan tiap perpanjangan.
     *
     * Trial bernilai 0 karena masa ujinya dihitung dalam hari
     * (lihat KondisiLangganan::LAMA_TRIAL_HARI), bukan bulan.
     */
    public function bulan(): int
    {
        return match ($this) {
            self::Trial => 0,
            self::Bulanan => 1,
            self::Semesteran => 6,
            self::Tahunan => 12,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Trial => 'Uji Coba',
            self::Bulanan => '1 Bulan',
            self::Semesteran => '6 Bulan',
            self::Tahunan => '12 Bulan',
        };
    }

    /**
     * Durasi yang boleh dibeli atau diperpanjang manual — trial tidak termasuk.
     *
     * @return list<self>
     */
    public static function berbayar(): array
    {
        return [self::Bulanan, self::Semesteran, self::Tahunan];
    }
}
