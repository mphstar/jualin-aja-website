<?php

declare(strict_types=1);

namespace App\Support;

use App\Exceptions\KesalahanDomain;
use App\Models\Produk;

/**
 * Satu-satunya tempat stok produk berubah.
 *
 * **Stok tidak boleh minus.** Aturan itu dijaga berlapis, dan ketiganya
 * dibutuhkan karena masing-masing menutup lubang yang tidak ditutup yang lain:
 *
 * 1. **Kolom UNSIGNED** di migrasi — pertahanan terakhir di sisi basis data.
 * 2. **`lockForUpdate()` di dalam transaksi** — dua kasir yang menjual barang
 *    terakhir pada saat bersamaan akan diantre, bukan sama-sama membaca
 *    "sisa 1" lalu sama-sama menguranginya.
 * 3. **`UPDATE ... WHERE stok >= n`** — pengurangannya sendiri bersyarat.
 *    Kalau baris yang terpengaruh nol, artinya ada yang mendahului sejak
 *    pembacaan tadi, dan transaksinya dibatalkan alih-alih menulis angka
 *    negatif.
 *
 * Lapis ketiga yang paling penting: ia benar bahkan pada basis data yang
 * mengabaikan kunci baris (SQLite mengunci seluruh berkas, MySQL dengan
 * isolasi longgar bisa melepaskannya lebih awal).
 */
final class PenjagaStok
{
    /**
     * Kunci baris produk yang akan disentuh, dalam urutan id menaik.
     *
     * Urutannya sengaja pasti. Dua transaksi yang mengunci produk yang sama
     * dengan urutan berbeda adalah resep deadlock — dan deadlock di kasir
     * muncul sebagai transaksi yang gagal tepat saat antrean paling panjang.
     *
     * @param  list<int>  $produkId
     * @return array<int, Produk> produk terkunci, berindeks id
     */
    public static function kunci(array $produkId): array
    {
        $produkId = array_values(array_unique($produkId));

        if ($produkId === []) {
            return [];
        }

        sort($produkId);

        return Produk::query()
            ->whereIn('id', $produkId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id')
            ->all();
    }

    /**
     * Terapkan selisih stok: negatif mengurangi, positif mengembalikan.
     *
     * Menerima SELISIH, bukan nilai akhir. Menghitung ulang nilai akhir dari
     * pembacaan sebelumnya berarti menimpa perubahan transaksi lain yang
     * menyelip di antaranya; selisih selalu benar berapa pun yang terjadi di
     * sela-selanya.
     *
     * @param  array<int, int>  $selisih  produk id => perubahan stok
     * @param  array<int, Produk>  $terkunci  hasil dari kunci(), untuk pesan galat
     */
    public static function terapkan(array $selisih, array $terkunci): void
    {
        foreach ($selisih as $produkId => $delta) {
            $produk = $terkunci[$produkId] ?? null;

            if ($produk === null || ! $produk->lacak_stok || $delta === 0) {
                continue;
            }

            if ($delta > 0) {
                Produk::query()->whereKey($produkId)->increment('stok', $delta);

                continue;
            }

            $butuh = -$delta;

            /*
             * Bersyarat, bukan `decrement()` polos. Baris terpengaruh nol
             * berarti stoknya sudah tidak cukup — entah karena memang kurang
             * sejak awal, atau karena kasir lain mendahului sepersekian detik
             * yang lalu. Keduanya harus membatalkan transaksi ini.
             */
            $terpengaruh = Produk::query()
                ->whereKey($produkId)
                ->where('stok', '>=', $butuh)
                ->decrement('stok', $butuh);

            if ($terpengaruh === 0) {
                throw new KesalahanDomain(sprintf(
                    'Stok %s tinggal %d %s, tidak cukup untuk %d.',
                    $produk->nama,
                    $produk->stok,
                    $produk->satuan,
                    $butuh,
                ));
            }
        }
    }
}
