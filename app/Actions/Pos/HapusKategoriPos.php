<?php

declare(strict_types=1);

namespace App\Actions\Pos;

use App\Exceptions\KesalahanDomain;
use App\Models\Kategori;
use App\Models\PosUser;
use App\Models\Produk;
use Illuminate\Support\Facades\DB;

/**
 * Hapus kategori, sekaligus MEMINDAHKAN produknya.
 *
 * `pindahkanKe` wajib diisi kalau kategorinya masih berisi produk. Produk tanpa
 * kategori yang ada adalah produk yang hilang dari layar Produk dan dari kasir
 * sekaligus — masih tersimpan, tapi tidak bisa dijual maupun disunting.
 * Menghapus diam-diam jauh lebih merusak daripada menolak menghapus, karena
 * kehilangannya baru terasa berminggu-minggu kemudian.
 *
 * Kategori terakhir tidak boleh dihapus: formulir produk selalu butuh
 * setidaknya satu kategori untuk dipilih.
 */
final readonly class HapusKategoriPos
{
    public function __invoke(PosUser $toko, Kategori $kategori, ?int $pindahkanKe = null): void
    {
        DB::transaction(function () use ($toko, $kategori, $pindahkanKe): void {
            $jumlahKategori = Kategori::query()->where('pos_user_id', $toko->id)->count();

            if ($jumlahKategori <= 1) {
                throw new KesalahanDomain('Kategori terakhir tidak bisa dihapus.');
            }

            $punyaProduk = Produk::query()
                ->where('pos_user_id', $toko->id)
                ->where('kategori_id', $kategori->id)
                ->exists();

            if ($punyaProduk) {
                $tujuan = $pindahkanKe === null || $pindahkanKe === $kategori->id
                    ? null
                    : Kategori::query()
                        ->where('pos_user_id', $toko->id)
                        ->whereKey($pindahkanKe)
                        ->first();

                if ($tujuan === null) {
                    throw new KesalahanDomain('Pilih dulu kategori tujuan produknya.');
                }

                Produk::query()
                    ->where('pos_user_id', $toko->id)
                    ->where('kategori_id', $kategori->id)
                    ->update(['kategori_id' => $tujuan->id]);
            }

            $kategori->delete();
        }, attempts: 3);
    }
}
