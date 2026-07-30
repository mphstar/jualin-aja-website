<?php

declare(strict_types=1);

namespace App\Actions\Pos;

use App\Enums\MetodeBayarPos;
use App\Enums\StatusTransaksi;
use App\Exceptions\KesalahanDomain;
use App\Models\PosUser;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Support\PenjagaStok;
use Illuminate\Support\Facades\DB;

/**
 * Catat satu penjualan.
 *
 * Seluruhnya di dalam SATU transaksi basis data, dan itu bukan formalitas:
 * penjualan menyentuh empat tabel sekaligus (nomor struk di `pos_users`,
 * `transaksi`, `transaksi_baris`, stok di `produk`). Tanpa transaksi, jaringan
 * yang putus di tengah meninggalkan struk tanpa baris, atau stok yang sudah
 * berkurang untuk penjualan yang tidak pernah tercatat — dan keduanya tidak
 * bisa ditemukan lagi kecuali ada yang kebetulan menghitung ulang rak.
 *
 * Percobaan ulang tiga kali menangani deadlock: dua kasir yang menjual produk
 * yang sama pada detik yang sama akan saling menunggu, dan yang kalah
 * diulang dari awal alih-alih gagal di depan pembeli.
 */
final readonly class SimpanTransaksiPos
{
    /**
     * @param  array<int, int>  $item  produk id => jumlah, urut sesuai keranjang
     */
    public function __invoke(
        PosUser $toko,
        array $item,
        MetodeBayarPos $metode,
        StatusTransaksi $status,
        ?string $pelanggan = null,
        ?int $uangDiterima = null,
    ): Transaksi {
        if ($item === []) {
            throw new KesalahanDomain('Transaksi harus berisi setidaknya satu barang.');
        }

        if ($status === StatusTransaksi::Ditahan && trim((string) $pelanggan) === '') {
            throw new KesalahanDomain('Nama pembeli wajib diisi untuk transaksi bayar nanti.');
        }

        return DB::transaction(function () use ($toko, $item, $metode, $status, $pelanggan, $uangDiterima): Transaksi {
            $produk = PenjagaStok::kunci(array_keys($item));
            $baris = $this->susunBaris($toko, $item, $produk);

            $total = array_sum(array_map(
                static fn (array $b): int => $b['harga_satuan'] * $b['jumlah'],
                $baris,
            ));

            $this->periksaUang($metode, $status, $total, $uangDiterima);

            if ($status->mengurangiStok()) {
                PenjagaStok::terapkan(
                    array_map(static fn (int $jumlah): int => -$jumlah, $item),
                    $produk,
                );
            }

            $sesiAktif = \App\Models\SesiKasir::query()
                ->where('pos_user_id', $toko->id)
                ->whereNull('waktu_tutup')
                ->latest('waktu_buka')
                ->first();

            $transaksi = Transaksi::query()->create([
                'pos_user_id' => $toko->id,
                'sesi_kasir_id' => $sesiAktif?->id,
                'nama_kasir' => $sesiAktif?->nama_kasir,
                'nomor_struk' => (new NomorStrukBerikutnya)($toko),
                'waktu' => now(),
                'metode' => $metode,
                'status' => $status,
                'pelanggan' => $status === StatusTransaksi::Ditahan ? trim((string) $pelanggan) : null,
                // Uang diterima hanya berarti untuk tunai yang sudah selesai.
                // Menyimpannya untuk metode lain berarti menyimpan angka yang
                // tidak pernah benar-benar berpindah tangan.
                'uang_diterima' => $metode === MetodeBayarPos::Tunai && $status === StatusTransaksi::Selesai
                    ? $uangDiterima
                    : null,
            ]);

            $transaksi->baris()->createMany($baris);

            return $transaksi->load('baris');
        }, attempts: 3);
    }

    /**
     * Ubah keranjang jadi baris struk, dengan nama & harga disalin saat ini.
     *
     * @param  array<int, int>  $item
     * @param  array<int, Produk>  $produk
     * @return list<array<string, mixed>>
     */
    private function susunBaris(PosUser $toko, array $item, array $produk): array
    {
        $baris = [];

        foreach ($item as $produkId => $jumlah) {
            if ($jumlah < 1) {
                throw new KesalahanDomain('Jumlah setiap barang harus minimal satu.');
            }

            $p = $produk[$produkId] ?? null;

            /*
             * Produk milik toko lain diperlakukan sama dengan produk yang tidak
             * ada. Membedakan keduanya lewat pesan galat memberi tahu penyerang
             * id mana yang benar-benar terpakai.
             */
            if ($p === null || $p->pos_user_id !== $toko->id) {
                throw new KesalahanDomain('Ada produk yang tidak dikenali di keranjang.');
            }

            $baris[] = [
                'produk_id' => $p->id,
                'nama' => $p->nama,
                'harga_satuan' => $p->harga_jual,
                'jumlah' => $jumlah,
            ];
        }

        return $baris;
    }

    /**
     * Tunai yang sudah selesai wajib cukup.
     *
     * Metode lain tidak menuntut nominal — yang dikonfirmasi kasir di situ
     * adalah dananya sudah masuk, bukan berapa lembar yang ia terima.
     */
    private function periksaUang(
        MetodeBayarPos $metode,
        StatusTransaksi $status,
        int $total,
        ?int $uangDiterima,
    ): void {
        if ($metode !== MetodeBayarPos::Tunai || $status !== StatusTransaksi::Selesai) {
            return;
        }

        if ($uangDiterima === null || $uangDiterima < $total) {
            throw new KesalahanDomain('Uang yang diterima belum menutup total tagihan.');
        }
    }
}
