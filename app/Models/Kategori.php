<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IkonKategori;
use Database\Factories\KategoriFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kategori produk milik satu toko.
 *
 * @property int $id
 * @property int $pos_user_id
 * @property string $nama
 * @property IkonKategori $ikon
 * @property int $urutan
 * @property-read PosUser $posUser
 */
#[Fillable(['pos_user_id', 'nama', 'ikon', 'urutan'])]
class Kategori extends Model
{
    /** @use HasFactory<KategoriFactory> */
    use HasFactory;

    protected $table = 'kategori';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'ikon' => IkonKategori::class,
            'urutan' => 'integer',
        ];
    }

    /** @return BelongsTo<PosUser, $this> */
    public function posUser(): BelongsTo
    {
        return $this->belongsTo(PosUser::class);
    }

    /** @return HasMany<Produk, $this> */
    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class);
    }

    /**
     * Batasi ke satu toko.
     *
     * Dipakai setiap kueri POS tanpa kecuali. Menaruhnya sebagai scope, bukan
     * `where` yang diketik ulang di tiap controller, membuat satu tempat yang
     * terlewat jadi mustahil — dan satu yang terlewat berarti kasir toko lain
     * melihat menu yang bukan miliknya.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function milik(Builder $query, PosUser $posUser): void
    {
        $query->where('kategori.pos_user_id', $posUser->id);
    }

    /** @param  Builder<$this>  $query */
    #[Scope]
    protected function terurut(Builder $query): void
    {
        $query->orderBy('kategori.urutan')->orderBy('kategori.id');
    }
}
