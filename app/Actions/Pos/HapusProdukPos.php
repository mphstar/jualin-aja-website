<?php

declare(strict_types=1);

namespace App\Actions\Pos;

use App\Models\PosUser;
use App\Models\Produk;
use Illuminate\Support\Facades\DB;

/**
 * Hapus produk milik toko.
 *
 * `transaksi_baris` yang merujuk produk ini akan otomatis mengatur `produk_id`
 * menjadi null (nullOnDelete) sehingga riwayat transaksi & struk lama tetap
 * utuh tanpa merusak nama dan harga historisnya.
 */
final readonly class HapusProdukPos
{
    public function __invoke(PosUser $toko, Produk $produk): void
    {
        DB::transaction(function () use ($produk): void {
            $produk->delete();
        }, attempts: 3);
    }
}
