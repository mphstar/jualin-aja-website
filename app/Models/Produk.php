<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProdukFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Barang yang dijual satu toko.
 *
 * @property int $id
 * @property int $pos_user_id
 * @property int $kategori_id
 * @property string $nama
 * @property int $harga_jual
 * @property string $satuan
 * @property bool $lacak_stok
 * @property int $stok
 * @property string|null $gambar_url
 * @property-read Kategori $kategori
 * @property-read PosUser $posUser
 */
#[Fillable([
    'pos_user_id', 'kategori_id', 'nama', 'harga_jual',
    'satuan', 'lacak_stok', 'stok', 'gambar_url',
])]
class Produk extends Model
{
    /** @use HasFactory<ProdukFactory> */
    use HasFactory;

    /** Di bawah angka ini produk ditandai menipis di layar kasir. */
    public const int AMBANG_MENIPIS = 5;

    protected $table = 'produk';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'harga_jual' => 'integer',
            'lacak_stok' => 'boolean',
            'stok' => 'integer',
        ];
    }

    /** @return BelongsTo<Kategori, $this> */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    /** @return BelongsTo<PosUser, $this> */
    public function posUser(): BelongsTo
    {
        return $this->belongsTo(PosUser::class);
    }

    public function habis(): bool
    {
        return $this->lacak_stok && $this->stok <= 0;
    }

    public function menipis(): bool
    {
        return $this->lacak_stok && $this->stok > 0 && $this->stok <= self::AMBANG_MENIPIS;
    }

    /**
     * Batasi ke satu toko.
     *
     * Kolomnya ditulis lengkap dengan nama tabel. Daftar produk di-join ke
     * `kategori` untuk mengikuti urutan chip kasir, dan kedua tabel punya
     * `pos_user_id` — tanpa kualifikasi, kuerinya gagal dengan "ambiguous
     * column" tepat di endpoint yang paling sering dipanggil.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function milik(Builder $query, PosUser $posUser): void
    {
        $query->where('produk.pos_user_id', $posUser->id);
    }
}
