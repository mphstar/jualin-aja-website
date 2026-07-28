<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

/**
 * Aturan dan pembacaan keranjang, dipakai bersama oleh simpan transaksi dan
 * ubah isi piutang.
 *
 * Bentuk yang diterima adalah LIST, bukan peta produkId → jumlah: JSON object
 * berkunci angka gampang berubah jadi array saat lewat beberapa lapis
 * serialisasi, dan list bertanda jelas tidak punya masalah itu. Peta baru
 * disusun di sini, sekaligus menjumlahkan baris kembar — keranjang yang
 * mengirim produk yang sama dua kali adalah keranjang yang tidak boleh
 * menghasilkan dua baris struk.
 */
trait PunyaItemKeranjang
{
    /** @return array<string, mixed> */
    protected function aturanItem(): array
    {
        return [
            'item' => ['required', 'array', 'min:1', 'max:200'],
            'item.*.produkId' => ['required', 'integer', 'min:1'],
            'item.*.jumlah' => ['required', 'integer', 'min:1', 'max:9999'],
        ];
    }

    /** @return array<int, int> produk id => jumlah, urut sesuai keranjang */
    public function item(): array
    {
        $item = [];

        /** @var list<array{produkId: int|string, jumlah: int|string}> $masuk */
        $masuk = (array) $this->input('item', []);

        foreach ($masuk as $baris) {
            $produkId = (int) $baris['produkId'];
            $item[$produkId] = ($item[$produkId] ?? 0) + (int) $baris['jumlah'];
        }

        return $item;
    }
}
