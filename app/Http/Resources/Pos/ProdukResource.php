<?php

declare(strict_types=1);

namespace App\Http\Resources\Pos;

use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Padanan `Produk` di aplikasi Flutter.
 *
 * `habis` dan `menipis` ikut dikirim walau bisa dihitung di klien: keduanya
 * memakai ambang yang ditentukan server (Produk::AMBANG_MENIPIS), dan ambang
 * yang digandakan di dua bahasa adalah ambang yang cepat atau lambat berbeda.
 *
 * @mixin Produk
 */
class ProdukResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'nama' => $this->nama,
            'kategoriId' => (string) $this->kategori_id,
            'hargaJual' => $this->harga_jual,
            'satuan' => $this->satuan,
            'lacakStok' => $this->lacak_stok,
            'stok' => $this->stok,
            'gambarUrl' => $this->gambar_url,
            'habis' => $this->habis(),
            'menipis' => $this->menipis(),
        ];
    }
}
