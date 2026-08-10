<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\StatusLangganan;
use App\Enums\VersiLangganan;

/**
 * Sumber kebenaran TUNGGAL untuk matriks fitur dan batasan paket langganan.
 *
 * Mengatur hak akses fitur (resep, voucher/diskon, batasan produk) berdasarkan
 * versi langganan (Gratis, Trial, Langganan).
 */
final class FiturLangganan
{
    /**
     * Konversi dari StatusLangganan ke VersiLangganan.
     */
    public static function versiDariStatus(?StatusLangganan $status): VersiLangganan
    {
        return match ($status) {
            StatusLangganan::Trial => VersiLangganan::Trial,
            StatusLangganan::Aktif, StatusLangganan::AkanBerakhir => VersiLangganan::Langganan,
            default => VersiLangganan::Gratis,
        };
    }

    /**
     * Apakah versi ini boleh mengakses katalog resep ebook?
     *
     * Rules:
     * - Gratis: ❌ Tidak
     * - Trial: ❌ Tidak
     * - Langganan: ✅ Ya
     */
    public static function bolehAksesResep(VersiLangganan $versi): bool
    {
        return $versi === VersiLangganan::Langganan;
    }

    /**
     * Apakah versi ini boleh menggunakan fitur voucher/diskon transaksi?
     *
     * Rules:
     * - Gratis: ❌ Tidak
     * - Trial: ✅ Ya
     * - Langganan: ✅ Ya
     */
    public static function bolehAksesVoucher(VersiLangganan $versi): bool
    {
        return match ($versi) {
            VersiLangganan::Gratis => false,
            VersiLangganan::Trial, VersiLangganan::Langganan => true,
        };
    }

    /**
     * Apakah versi ini boleh membuat/mencatat transaksi kasir baru?
     *
     * Rules:
     * - Gratis: ✅ Ya (Tetap boleh membuat transaksi)
     * - Trial: ✅ Ya
     * - Langganan: ✅ Ya
     */
    public static function bolehTransaksi(VersiLangganan $versi): bool
    {
        return true;
    }

    /**
     * Batas maksimal produk untuk versi ini.
     * Return null jika tidak ada batas (unlimited).
     *
     * Rules:
     * - Gratis: 20 (bisa dikustomisasi via config/langganan.php)
     * - Trial: null (unlimited)
     * - Langganan: null (unlimited)
     */
    public static function batasMaksimalProduk(VersiLangganan $versi): ?int
    {
        return match ($versi) {
            VersiLangganan::Gratis => (function (): int {
                try {
                    return (int) (config('langganan.batas_produk_gratis') ?? 20);
                } catch (\Throwable) {
                    return 20;
                }
            })(),
            VersiLangganan::Trial, VersiLangganan::Langganan => null,
        };
    }

    /**
     * Apakah penambahan produk sejumlah $tambahan diperbolehkan?
     */
    public static function bolehTambahProduk(
        VersiLangganan $versi,
        int $jumlahProdukSaatIni,
        int $tambahan = 1,
    ): bool {
        $batas = self::batasMaksimalProduk($versi);

        if ($batas === null) {
            return true;
        }

        return ($jumlahProdukSaatIni + $tambahan) <= $batas;
    }
}
