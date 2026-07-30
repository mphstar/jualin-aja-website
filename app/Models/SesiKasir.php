<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Model data Sesi Shift Kasir.
 *
 * @property int $id
 * @property int $pos_user_id
 * @property string $kode_sesi
 * @property string $nama_kasir
 * @property Carbon $waktu_buka
 * @property Carbon|null $waktu_tutup
 * @property int $kas_awal_tunai
 * @property int $kas_awal_qris
 * @property int $kas_awal_transfer
 * @property int $total_tunai
 * @property int $total_qris
 * @property int $total_transfer
 * @property int $jumlah_transaksi
 * @property int|null $kas_fisik_tunai
 * @property int|null $kas_fisik_qris
 * @property int|null $kas_fisik_transfer
 * @property string|null $catatan
 * @property-read PosUser $posUser
 */
#[Fillable([
    'pos_user_id', 'kode_sesi', 'nama_kasir', 'waktu_buka', 'waktu_tutup',
    'kas_awal_tunai', 'kas_awal_qris', 'kas_awal_transfer',
    'total_tunai', 'total_qris', 'total_transfer', 'jumlah_transaksi',
    'kas_fisik_tunai', 'kas_fisik_qris', 'kas_fisik_transfer', 'catatan',
])]
class SesiKasir extends Model
{
    use HasFactory;

    protected $table = 'sesi_kasir';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'waktu_buka' => 'datetime',
            'waktu_tutup' => 'datetime',
            'kas_awal_tunai' => 'integer',
            'kas_awal_qris' => 'integer',
            'kas_awal_transfer' => 'integer',
            'total_tunai' => 'integer',
            'total_qris' => 'integer',
            'total_transfer' => 'integer',
            'jumlah_transaksi' => 'integer',
            'kas_fisik_tunai' => 'integer',
            'kas_fisik_qris' => 'integer',
            'kas_fisik_transfer' => 'integer',
        ];
    }

    /** @return BelongsTo<PosUser, $this> */
    public function posUser(): BelongsTo
    {
        return $this->belongsTo(PosUser::class);
    }
}
