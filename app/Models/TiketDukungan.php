<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JenisTiket;
use App\Enums\PrioritasTiket;
use App\Enums\StatusTiket;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $pos_user_id
 * @property string $nomor_tiket
 * @property JenisTiket $jenis
 * @property string $subjek
 * @property string $pesan
 * @property StatusTiket $status
 * @property PrioritasTiket $prioritas
 * @property string|null $balasan_admin
 * @property Carbon|null $dibalas_pada
 * @property int|null $dibalas_oleh_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read PosUser $posUser
 * @property-read User|null $admin
 */
#[Fillable([
    'pos_user_id', 'nomor_tiket', 'jenis', 'subjek', 'pesan',
    'status', 'prioritas', 'balasan_admin', 'dibalas_pada', 'dibalas_oleh_id',
])]
class TiketDukungan extends Model
{
    use HasFactory;

    protected $table = 'tiket_dukungan';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'jenis' => JenisTiket::class,
            'status' => StatusTiket::class,
            'prioritas' => PrioritasTiket::class,
            'dibalas_pada' => 'datetime',
        ];
    }

    /** @return BelongsTo<PosUser, $this> */
    public function posUser(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'pos_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibalas_oleh_id');
    }

    public static function buatNomorTiket(): string
    {
        $prefix = 'TKT-' . now()->format('Ymd');
        $terakhir = self::query()
            ->where('nomor_tiket', 'like', "{$prefix}-%")
            ->latest('id')
            ->value('nomor_tiket');

        $urut = 1;
        if ($terakhir && preg_match('/-(\d{4})$/', $terakhir, $m)) {
            $urut = ((int) $m[1]) + 1;
        }

        return sprintf('%s-%04d', $prefix, $urut);
    }
}
