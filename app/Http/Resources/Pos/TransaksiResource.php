<?php

declare(strict_types=1);

namespace App\Http\Resources\Pos;

use App\Models\BarisTransaksi;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Padanan `Transaksi` di aplikasi Flutter.
 *
 * `total`, `jumlahItem`, dan `kembalian` dikirim walau ketiganya diturunkan
 * dari `baris`. Bukan denormalisasi: klien menghitungnya sendiri juga, dan
 * angka di sini yang jadi pembanding kalau suatu hari keduanya berbeda —
 * struk yang totalnya berbeda antara layar dan server adalah struk yang tidak
 * bisa dipakai untuk berdebat dengan pembeli.
 *
 * @mixin Transaksi
 */
class TransaksiResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'sesiId' => $this->sesi_kasir_id ? (string) $this->sesi_kasir_id : null,
            'namaKasir' => $this->nama_kasir,
            'nomorStruk' => $this->nomor_struk,
            'waktu' => $this->waktu->toISOString(),
            'metode' => $this->metode->value,
            'status' => $this->status->value,
            'pelanggan' => $this->pelanggan,
            'uangDiterima' => $this->uang_diterima,
            'subtotal' => $this->subtotal(),
            'diskonTipe' => $this->diskon_tipe,
            'diskonNilai' => $this->diskon_nilai,
            'diskonNominal' => $this->diskon_nominal,
            'total' => $this->total(),
            'jumlahItem' => $this->jumlahItem(),
            'kembalian' => $this->kembalian(),
            'baris' => $this->baris->map(fn (BarisTransaksi $b): array => [
                'produkId' => $b->produk_id === null ? null : (string) $b->produk_id,
                'nama' => $b->nama,
                'hargaSatuan' => $b->harga_satuan,
                'jumlah' => $b->jumlah,
                'subtotal' => $b->subtotal(),
            ])->all(),
        ];
    }
}
