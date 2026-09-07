<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Saluran pembayaran Mayar yang ditawarkan aplikasi.
 *
 * Daftarnya sengaja pendek dan DIPUTUSKAN SERVER. Nilai-nilai `paymentMethod`
 * diambil dari halaman "Create Invoice" dokumen Mayar V2 — yang tidak terdaftar
 * di sini tidak akan pernah sampai ke create call. Saluran harus aktif di akun
 * Mayar; kalau tidak, create membalas 400.
 *
 * @see https://docs.mayar.id/api-reference-v2/invoice/create.md
 */
enum SaluranBayar: string
{
    case Qris = 'qris';

    public function label(): string
    {
        return match ($this) {
            self::Qris => 'QRIS',
        };
    }

    /** Pengelompokan kasar untuk laporan admin. */
    public function grup(): MetodePembayaran
    {
        return match ($this) {
            self::Qris => MetodePembayaran::Qris,
        };
    }

    /** True kalau saluran ini memberi nomor yang harus disalin pembayar. */
    public function pakaiKode(): bool
    {
        return false;
    }

    /**
     * Saluran yang boleh dipilih pembeli — satu-satunya sumber kebenaran.
     *
     * @return list<self>
     */
    public static function daftar(): array
    {
        return [self::Qris];
    }
}
