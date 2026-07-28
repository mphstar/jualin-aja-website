<?php

declare(strict_types=1);

namespace App\Actions\Pos;

use App\Enums\StatusTransaksi;
use App\Exceptions\KesalahanDomain;
use App\Models\BarisTransaksi;
use App\Models\PosUser;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Support\PenjagaStok;
use Illuminate\Support\Facades\DB;

/**
 * Ubah isi piutang yang belum lunas.
 *
 * **Hanya yang ditahan.** Struk yang sudah lunas tidak boleh berubah isinya
 * dengan alasan apa pun: uangnya sudah diterima, angkanya sudah masuk laporan,
 * dan kertasnya sudah di tangan pembeli.
 *
 * Stok dikoreksi lewat SELISIH, bukan dihitung ulang dari nol. Mengembalikan
 * seluruh isi struk lalu menguranginya lagi memberi hasil yang sama di atas
 * kertas, tapi salah begitu ada transaksi lain yang menyentuh produk yang sama
 * di antara keduanya — dan di jam sibuk itu bukan kemungkinan teoretis.
 */
final readonly class UbahIsiTransaksiPos
{
    /** @param  array<int, int>  $item  produk id => jumlah baru */
    public function __invoke(PosUser $toko, Transaksi $transaksi, array $item): Transaksi
    {
        if ($item === []) {
            throw new KesalahanDomain('Pesanan harus berisi setidaknya satu barang.');
        }

        return DB::transaction(function () use ($toko, $transaksi, $item): Transaksi {
            $terkunci = Transaksi::query()
                ->whereKey($transaksi->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($terkunci->status !== StatusTransaksi::Ditahan) {
                throw new KesalahanDomain('Hanya piutang yang belum lunas yang bisa diubah.');
            }

            $terkunci->load('baris');

            $lama = $this->jumlahPerProduk($terkunci);
            $produk = PenjagaStok::kunci([...array_keys($lama), ...array_keys($item)]);

            $baris = $this->susunBaris($toko, $terkunci, $item, $produk);

            /*
             * Selisih dibalik tandanya: yang bertambah di struk berarti stok
             * BERKURANG. Produk yang hilang dari struk mengembalikan stoknya.
             */
            $selisih = [];
            foreach ([...array_keys($lama), ...array_keys($item)] as $produkId) {
                $selisih[$produkId] = ($lama[$produkId] ?? 0) - ($item[$produkId] ?? 0);
            }

            PenjagaStok::terapkan($selisih, $produk);

            $terkunci->baris()->delete();
            $terkunci->baris()->createMany($baris);

            return $terkunci->load('baris');
        }, attempts: 3);
    }

    /**
     * Isi struk sekarang, dijumlahkan per produk.
     *
     * @return array<int, int>
     */
    private function jumlahPerProduk(Transaksi $transaksi): array
    {
        $jumlah = [];

        foreach ($transaksi->baris as $baris) {
            if ($baris->produk_id === null) {
                continue;
            }

            $jumlah[$baris->produk_id] = ($jumlah[$baris->produk_id] ?? 0) + $baris->jumlah;
        }

        return $jumlah;
    }

    /**
     * Baris baru, dengan harga baris lama dipertahankan.
     *
     * Pembeli mengambil barangnya pada harga hari itu; menyegarkan harganya
     * sekarang berarti menagih selisih yang tidak pernah disepakati. Baris yang
     * benar-benar baru dicatat pada harga hari ini — ia memang barang yang baru
     * saja diambil.
     *
     * @param  array<int, int>  $item
     * @param  array<int, Produk>  $produk
     * @return list<array<string, mixed>>
     */
    private function susunBaris(PosUser $toko, Transaksi $transaksi, array $item, array $produk): array
    {
        /** @var array<int, BarisTransaksi> $hargaLama */
        $hargaLama = $transaksi->baris
            ->filter(static fn (BarisTransaksi $b): bool => $b->produk_id !== null)
            ->keyBy('produk_id')
            ->all();

        $baris = [];

        foreach ($item as $produkId => $jumlah) {
            if ($jumlah < 1) {
                throw new KesalahanDomain('Jumlah setiap barang harus minimal satu.');
            }

            $p = $produk[$produkId] ?? null;

            if ($p === null || $p->pos_user_id !== $toko->id) {
                throw new KesalahanDomain('Ada produk yang tidak dikenali di pesanan.');
            }

            $sebelumnya = $hargaLama[$produkId] ?? null;

            $baris[] = $sebelumnya === null
                ? [
                    'produk_id' => $p->id,
                    'nama' => $p->nama,
                    'harga_satuan' => $p->harga_jual,
                    'jumlah' => $jumlah,
                ]
                : [
                    'produk_id' => $p->id,
                    'nama' => $sebelumnya->nama,
                    'harga_satuan' => $sebelumnya->harga_satuan,
                    'jumlah' => $jumlah,
                ];
        }

        return $baris;
    }
}
