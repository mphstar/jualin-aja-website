<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusPembayaran: string
{
    case Lunas = 'LUNAS';
    case Menunggu = 'MENUNGGU';
    case Gagal = 'GAGAL';

    /**
     * Batas waktunya lewat tanpa dibayar.
     *
     * Sengaja dipisah dari `Gagal`. Gagal berarti ada yang mencoba membayar
     * dan ditolak — kartu ditolak bank, saldo kurang. Kedaluwarsa berarti
     * tidak pernah ada percobaan sama sekali. Menyatukannya membuat angka
     * "pembayaran gagal" di dasbor terlihat mengkhawatirkan padahal isinya
     * orang yang sekadar berubah pikiran.
     */
    case Kedaluwarsa = 'KEDALUWARSA';
    case Refund = 'REFUND';

    public function label(): string
    {
        return match ($this) {
            self::Lunas => 'Lunas',
            self::Menunggu => 'Menunggu',
            self::Gagal => 'Gagal',
            self::Kedaluwarsa => 'Kedaluwarsa',
            self::Refund => 'Refund',
        };
    }

    /** Tidak akan berubah lagi tanpa campur tangan manusia. */
    public function final(): bool
    {
        return $this !== self::Menunggu;
    }
}
