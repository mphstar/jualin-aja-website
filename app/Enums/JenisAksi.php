<?php

declare(strict_types=1);

namespace App\Enums;

/** Daftar tertutup aksi yang wajib tercatat di log aktivitas (PRD §F7.2). */
enum JenisAksi: string
{
    case Masuk = 'MASUK';
    case Keluar = 'KELUAR';
    case LanggananPerpanjang = 'LANGGANAN_PERPANJANG';
    case UserTangguhkan = 'USER_TANGGUHKAN';
    case UserPulihkan = 'USER_PULIHKAN';
    case EbookTambah = 'EBOOK_TAMBAH';
    case EbookUbah = 'EBOOK_UBAH';
    case EbookTerbitkan = 'EBOOK_TERBITKAN';
    case EbookJadikanDraf = 'EBOOK_JADIKAN_DRAF';
    case EbookHapus = 'EBOOK_HAPUS';
    case PembayaranLunas = 'PEMBAYARAN_LUNAS';
    case PembayaranGagal = 'PEMBAYARAN_GAGAL';
    case PengaturanUbah = 'PENGATURAN_UBAH';
    case PenggunaDitambahkan = 'PENGGUNA_DITAMBAHKAN';

    public function label(): string
    {
        return match ($this) {
            self::Masuk => 'Masuk',
            self::Keluar => 'Keluar',
            self::LanggananPerpanjang => 'Perpanjang Langganan',
            self::UserTangguhkan => 'Tangguhkan User',
            self::UserPulihkan => 'Pulihkan User',
            self::EbookTambah => 'Tambah Ebook',
            self::EbookUbah => 'Ubah Ebook',
            self::EbookTerbitkan => 'Terbitkan Ebook',
            self::EbookJadikanDraf => 'Jadikan Draf',
            self::EbookHapus => 'Hapus Ebook',
            self::PembayaranLunas => 'Tandai Lunas',
            self::PembayaranGagal => 'Tandai Gagal',
            self::PengaturanUbah => 'Ubah Pengaturan',
            self::PenggunaDitambahkan => 'Tambah Pengguna',
        };
    }
}
