<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Kategori khusus konten Prompt (PRD §4.3).
 *
 * Berbeda dari KategoriEbook yang untuk resep masakan; prompt (mis. untuk
 * menghasilkan gambar) memakai daftar sendiri agar filter tetap masuk akal.
 * Hanya terisi bila `jenis` bernilai PROMPT.
 */
enum KategoriPrompt: string
{
    case Logo = 'LOGO';
    case DesainMenu = 'DESAIN_MENU';
    case PosterPromosi = 'POSTER_PROMOSI';
    case SosialMedia = 'SOSIAL_MEDIA';
    case FotoProduk = 'FOTO_PRODUK';
    case KemasanProduk = 'KEMASAN_PRODUK';

    public function label(): string
    {
        return match ($this) {
            self::Logo => 'Logo',
            self::DesainMenu => 'Desain Menu',
            self::PosterPromosi => 'Poster Promosi',
            self::SosialMedia => 'Sosial Media',
            self::FotoProduk => 'Foto Produk',
            self::KemasanProduk => 'Kemasan Produk',
        };
    }
}
