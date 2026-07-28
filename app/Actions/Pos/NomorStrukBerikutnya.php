<?php

declare(strict_types=1);

namespace App\Actions\Pos;

use App\Models\PosUser;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Ambil nomor struk berikutnya untuk sebuah toko, sekaligus memesannya.
 *
 * Penghitungnya disimpan di `pos_users.nomor_struk_terakhir`, bukan dihitung
 * dari `MAX(nomor_struk)`. Dua alasan, dan yang kedua yang menentukan:
 *
 * 1. Menghapus transaksi lama tidak boleh membuat nomornya terpakai lagi —
 *    dua struk bernomor sama adalah dua struk yang tidak bisa dibedakan saat
 *    pembeli datang membawa kertasnya.
 * 2. `MAX + 1` yang dibaca dua permintaan bersamaan menghasilkan angka yang
 *    sama. Di sini barisnya dikunci lebih dulu, jadi yang kedua menunggu.
 *
 * WAJIB dipanggil dari dalam transaksi basis data yang sedang berjalan —
 * kuncinya baru dilepas saat transaksi itu selesai.
 */
final readonly class NomorStrukBerikutnya
{
    public function __invoke(PosUser $toko, ?CarbonImmutable $sekarang = null): string
    {
        $sekarang ??= CarbonImmutable::now();

        /** @var int $terakhir */
        $terakhir = DB::table('pos_users')
            ->where('id', $toko->id)
            ->lockForUpdate()
            ->value('nomor_struk_terakhir') ?? 0;

        $urutan = $terakhir + 1;

        DB::table('pos_users')
            ->where('id', $toko->id)
            ->update(['nomor_struk_terakhir' => $urutan]);

        $toko->setAttribute('nomor_struk_terakhir', $urutan);

        return sprintf('STR/%s/%s', $sekarang->year, str_pad((string) $urutan, 4, '0', STR_PAD_LEFT));
    }

    /** Nomor yang AKAN dipakai, tanpa memesannya. Dipakai layar kasir. */
    public function intip(PosUser $toko, ?CarbonImmutable $sekarang = null): string
    {
        $sekarang ??= CarbonImmutable::now();

        return sprintf(
            'STR/%s/%s',
            $sekarang->year,
            str_pad((string) ($toko->nomor_struk_terakhir + 1), 4, '0', STR_PAD_LEFT),
        );
    }
}
