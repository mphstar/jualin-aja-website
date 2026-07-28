<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris di dalam struk.
 *
 * `nama` dan `harga_satuan` adalah salinan saat transaksi terjadi, bukan
 * rujukan ke produk — struk kemarin harus tetap menunjukkan harga kemarin.
 *
 * @property int $id
 * @property int $transaksi_id
 * @property int|null $produk_id
 * @property string $nama
 * @property int $harga_satuan
 * @property int $jumlah
 * @property-read Transaksi $transaksi
 * @property-read Produk|null $produk
 */
#[Fillable(['transaksi_id', 'produk_id', 'nama', 'harga_satuan', 'jumlah'])]
class BarisTransaksi extends Model
{
    protected $table = 'transaksi_baris';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'harga_satuan' => 'integer',
            'jumlah' => 'integer',
        ];
    }

    /** @return BelongsTo<Transaksi, $this> */
    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    /** @return BelongsTo<Produk, $this> */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    public function subtotal(): int
    {
        return $this->harga_satuan * $this->jumlah;
    }
}
