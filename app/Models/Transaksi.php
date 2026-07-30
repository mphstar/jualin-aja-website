<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetodeBayarPos;
use App\Enums\StatusTransaksi;
use Database\Factories\TransaksiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Satu penjualan di kasir.
 *
 * @property int $id
 * @property int $pos_user_id
 * @property int|null $sesi_kasir_id
 * @property string|null $nama_kasir
 * @property string $nomor_struk
 * @property Carbon $waktu
 * @property MetodeBayarPos $metode
 * @property StatusTransaksi $status
 * @property string|null $pelanggan
 * @property int|null $uang_diterima
 * @property-read Collection<int, BarisTransaksi> $baris
 * @property-read PosUser $posUser
 * @property-read SesiKasir|null $sesiKasir
 */
#[Fillable([
    'pos_user_id', 'sesi_kasir_id', 'nama_kasir', 'nomor_struk', 'waktu', 'metode',
    'status', 'pelanggan', 'uang_diterima',
])]
class Transaksi extends Model
{
    /** @use HasFactory<TransaksiFactory> */
    use HasFactory;

    protected $table = 'transaksi';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
            'metode' => MetodeBayarPos::class,
            'status' => StatusTransaksi::class,
            'uang_diterima' => 'integer',
        ];
    }

    /** @return BelongsTo<PosUser, $this> */
    public function posUser(): BelongsTo
    {
        return $this->belongsTo(PosUser::class);
    }

    /** @return BelongsTo<SesiKasir, $this> */
    public function sesiKasir(): BelongsTo
    {
        return $this->belongsTo(SesiKasir::class);
    }

    /** @return HasMany<BarisTransaksi, $this> */
    public function baris(): HasMany
    {
        return $this->hasMany(BarisTransaksi::class);
    }

    /**
     * Diturunkan dari barisnya, tidak disimpan.
     *
     * Menuntut relasi `baris` sudah dimuat — `preventLazyLoading` akan
     * menjerit kalau lupa, dan itu memang yang diinginkan: total yang dihitung
     * dari relasi yang belum dimuat diam-diam mengembalikan nol.
     */
    public function total(): int
    {
        return $this->baris->sum(fn (BarisTransaksi $b): int => $b->subtotal());
    }

    public function jumlahItem(): int
    {
        return (int) $this->baris->sum('jumlah');
    }

    /** Kembalian, atau null kalau transaksi ini bukan tunai. */
    public function kembalian(): ?int
    {
        return $this->uang_diterima === null ? null : $this->uang_diterima - $this->total();
    }

    public function piutang(): bool
    {
        return $this->status === StatusTransaksi::Ditahan;
    }

    /** @param  Builder<$this>  $query */
    #[Scope]
    protected function milik(Builder $query, PosUser $posUser): void
    {
        $query->where('transaksi.pos_user_id', $posUser->id);
    }

    /**
     * Hanya yang dihitung sebagai omzet.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function dihitung(Builder $query): void
    {
        $query->where('transaksi.status', StatusTransaksi::Selesai->value);
    }
}
