<?php

declare(strict_types=1);

namespace App\Actions\Pos;

use App\Enums\IkonKategori;
use App\Models\Kategori;
use App\Models\PosUser;
use Illuminate\Support\Facades\DB;

/**
 * Tambah atau ubah kategori.
 *
 * Yang baru masuk ke BELAKANG, bukan depan. Urutan kategori menentukan urutan
 * chip di kasir, dan kategori baru yang menyerobot ke posisi pertama berarti
 * memindahkan tombol yang sudah dihafal jari kasir.
 */
final readonly class SimpanKategoriPos
{
    public function __invoke(
        PosUser $toko,
        string $nama,
        IkonKategori $ikon,
        ?Kategori $kategori = null,
    ): Kategori {
        return DB::transaction(function () use ($toko, $nama, $ikon, $kategori): Kategori {
            if ($kategori !== null) {
                $kategori->update(['nama' => $nama, 'ikon' => $ikon]);

                return $kategori;
            }

            /*
             * Dihitung di dalam transaksi supaya dua kategori yang dibuat
             * bersamaan tidak berebut urutan yang sama — urutan kembar membuat
             * posisi chip berpindah-pindah sendiri tiap kali daftar dimuat.
             */
            $urutanTerakhir = (int) Kategori::query()
                ->where('pos_user_id', $toko->id)
                ->lockForUpdate()
                ->max('urutan');

            return Kategori::query()->create([
                'pos_user_id' => $toko->id,
                'nama' => $nama,
                'ikon' => $ikon,
                'urutan' => $urutanTerakhir + 1,
            ]);
        }, attempts: 3);
    }
}
