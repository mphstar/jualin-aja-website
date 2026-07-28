<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Saluran Midtrans yang boleh dipilih pemilik toko untuk membayar langganan.
 *
 * Daftarnya sengaja pendek. Midtrans menyediakan belasan saluran, tapi halaman
 * yang menawarkan belasan pilihan membuat orang berhenti memilih — dan empat
 * saluran ini menutupi hampir semua pemilik toko di Indonesia.
 */
enum SaluranBayar: string
{
    case Qris = 'QRIS';
    case VaBca = 'VA_BCA';
    case VaMandiri = 'VA_MANDIRI';
    case Gopay = 'GOPAY';

    public function label(): string
    {
        return match ($this) {
            self::Qris => 'QRIS',
            self::VaBca => 'Virtual Account BCA',
            self::VaMandiri => 'Virtual Account Mandiri',
            self::Gopay => 'GoPay',
        };
    }

    /**
     * Pengelompokan kasar untuk laporan admin.
     *
     * QRIS dan GoPay sama-sama menghasilkan kode QR, tapi di laporan keduanya
     * memang dua saluran berbeda: yang satu rail antarbank, yang satu saldo
     * e-wallet.
     */
    public function metode(): MetodePembayaran
    {
        return match ($this) {
            self::Qris => MetodePembayaran::Qris,
            self::VaBca, self::VaMandiri => MetodePembayaran::VirtualAccount,
            self::Gopay => MetodePembayaran::Ewallet,
        };
    }

    /** True kalau saluran ini memberi nomor yang harus disalin pembayar. */
    public function pakaiKode(): bool
    {
        return $this === self::VaBca || $this === self::VaMandiri;
    }

    /**
     * `payment_type` yang dikirim ke Core API Midtrans.
     *
     * Mandiri memakai `echannel`, bukan `bank_transfer` — itu bukan
     * inkonsistensi Midtrans melainkan produk yang memang berbeda: yang
     * dihasilkan bukan nomor VA melainkan pasangan biller code + bill key.
     */
    public function tipePembayaranMidtrans(): string
    {
        return match ($this) {
            self::Qris => 'qris',
            self::VaBca => 'bank_transfer',
            self::VaMandiri => 'echannel',
            self::Gopay => 'gopay',
        };
    }
}
