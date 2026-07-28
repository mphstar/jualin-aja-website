<?php

declare(strict_types=1);

namespace App\Actions\Pos;

use App\Exceptions\KesalahanDomain;
use App\Models\Kategori;
use App\Models\PosUser;
use App\Models\Produk;
use Illuminate\Support\Facades\DB;

/**
 * Tambah atau ubah produk.
 *
 * @phpstan-type DataProduk array{
 *     nama: string,
 *     kategori_id: int,
 *     harga_jual: int,
 *     satuan: string,
 *     lacak_stok: bool,
 *     stok: int,
 *     gambar_url?: string|null,
 * }
 */
final readonly class SimpanProdukPos
{
    /** @param  DataProduk  $data */
    public function __invoke(PosUser $toko, array $data, ?Produk $produk = null): Produk
    {
        return DB::transaction(function () use ($toko, $data, $produk): Produk {
            $kategori = Kategori::query()
                ->where('pos_user_id', $toko->id)
                ->whereKey($data['kategori_id'])
                ->first();

            if ($kategori === null) {
                throw new KesalahanDomain('Kategori tidak ditemukan di toko ini.');
            }

            $atribut = [
                'pos_user_id' => $toko->id,
                'kategori_id' => $kategori->id,
                'nama' => $data['nama'],
                'harga_jual' => $data['harga_jual'],
                'satuan' => $data['satuan'],
                'lacak_stok' => $data['lacak_stok'],
                /*
                 * Stok hanya berarti kalau dilacak. Menyimpan angka sisa dari
                 * sakelar yang sudah dimatikan berarti menyimpan angka yang
                 * tidak pernah benar — dan yang akan muncul lagi, salah, pada
                 * hari sakelarnya dinyalakan kembali.
                 */
                'stok' => $data['lacak_stok'] ? $data['stok'] : 0,
                'gambar_url' => $data['gambar_url'] ?? $produk?->gambar_url,
            ];

            if ($produk === null) {
                return Produk::query()->create($atribut);
            }

            /*
             * Baris dikunci sebelum ditulis: menyunting produk sementara ada
             * penjualan berjalan atas produk yang sama akan menimpa stok yang
             * baru saja berkurang. Kunci membuat penyuntingan mengantre di
             * belakang penjualan, bukan menghapus jejaknya.
             */
            Produk::query()->whereKey($produk->getKey())->lockForUpdate()->firstOrFail();
            $produk->update($atribut);

            return $produk->refresh();
        }, attempts: 3);
    }
}
