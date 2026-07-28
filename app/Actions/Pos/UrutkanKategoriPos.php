<?php

declare(strict_types=1);

namespace App\Actions\Pos;

use App\Exceptions\KesalahanDomain;
use App\Models\Kategori;
use App\Models\PosUser;
use Illuminate\Support\Facades\DB;

/**
 * Simpan urutan kategori yang baru.
 *
 * Menerima daftar id UTUH, bukan "pindahkan indeks A ke B". Daftar utuh tidak
 * bisa salah menafsirkan pergeseran indeks setelah elemen diangkat — kesalahan
 * yang selalu meleset satu posisi dan selalu baru ketahuan setelah dipakai.
 *
 * Daftar yang tidak lengkap ditolak, bukan ditambal. Menambal berarti menebak
 * ke mana kategori yang hilang harus ditaruh, dan tebakan itu akan berbeda dari
 * apa yang dilihat pengguna di layarnya.
 */
final readonly class UrutkanKategoriPos
{
    /** @param  list<int>  $urutan  id kategori, dari paling depan */
    public function __invoke(PosUser $toko, array $urutan): void
    {
        DB::transaction(function () use ($toko, $urutan): void {
            $milikToko = Kategori::query()
                ->where('pos_user_id', $toko->id)
                ->lockForUpdate()
                ->pluck('id')
                ->all();

            sort($milikToko);
            $diminta = array_values(array_unique($urutan));
            sort($diminta);

            if ($milikToko !== $diminta) {
                throw new KesalahanDomain('Urutan harus memuat seluruh kategori toko, tepat satu kali.');
            }

            foreach ($urutan as $posisi => $id) {
                Kategori::query()
                    ->where('pos_user_id', $toko->id)
                    ->whereKey($id)
                    ->update(['urutan' => $posisi]);
            }
        }, attempts: 3);
    }
}
