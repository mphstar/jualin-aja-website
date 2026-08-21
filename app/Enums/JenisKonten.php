<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Jenis konten pustaka (PRD §4.3).
 *
 * Pustaka menampung dua macam konten: resep masakan dan prompt (mis. untuk
 * menghasilkan gambar). Keduanya sama-sama berbentuk PDF sehingga perlakuan
 * di aplikasi (pratinjau langsung, tanpa unduh) sama.
 */
enum JenisKonten: string
{
    case Resep = 'RESEP';
    case Prompt = 'PROMPT';

    public function label(): string
    {
        return match ($this) {
            self::Resep => 'Resep',
            self::Prompt => 'Prompt',
        };
    }
}
