<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lebar kertas printer termal.
 *
 * Dua ukuran ini yang beredar; angkanya menentukan berapa karakter muat per
 * baris, jadi ia bukan sekadar preferensi tampilan.
 */
enum LebarKertas: string
{
    case Mm58 = 'MM58';
    case Mm80 = 'MM80';

    public function label(): string
    {
        return match ($this) {
            self::Mm58 => '58 mm',
            self::Mm80 => '80 mm',
        };
    }

    /** Berapa karakter muat dalam satu baris cetak. */
    public function karakterPerBaris(): int
    {
        return match ($this) {
            self::Mm58 => 32,
            self::Mm80 => 48,
        };
    }
}
