<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Kondisi sebuah penjualan.
 *
 * `Ditahan` berarti barangnya sudah keluar tapi uangnya belum masuk — "bayar
 * nanti". Ia sengaja BUKAN status lunas: omzet hari itu tidak boleh ikut naik
 * hanya karena ada yang berjanji membayar besok.
 */
enum StatusTransaksi: string
{
    case Selesai = 'SELESAI';
    case Ditahan = 'DITAHAN';
    case Batal = 'BATAL';

    public function label(): string
    {
        return match ($this) {
            self::Selesai => 'Selesai',
            self::Ditahan => 'Bayar nanti',
            self::Batal => 'Batal',
        };
    }

    /** Hanya transaksi selesai yang dihitung sebagai omzet. */
    public function dihitung(): bool
    {
        return $this === self::Selesai;
    }

    /** Barang sudah keluar dari rak — stoknya berkurang. */
    public function mengurangiStok(): bool
    {
        return $this !== self::Batal;
    }
}
