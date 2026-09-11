<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $pos_user_id
 * @property int $ebook_id
 * @property string $jenis
 * @property string $tipe_akses
 * @property int|null $pembayaran_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read PosUser $posUser
 * @property-read Ebook $ebook
 * @property-read Pembayaran|null $pembayaran
 */
#[Fillable([
    'pos_user_id', 'ebook_id', 'jenis', 'tipe_akses', 'pembayaran_id',
])]
class AksesPustaka extends Model
{
    use HasFactory;

    protected $table = 'akses_pustaka';

    public const string TIPE_KLAIM_LANGGANAN = 'KLAIM_LANGGANAN';

    public const string TIPE_BELI_SATUAN = 'BELI_SATUAN';

    /** @return BelongsTo<PosUser, $this> */
    public function posUser(): BelongsTo
    {
        return $this->belongsTo(PosUser::class);
    }

    /** @return BelongsTo<Ebook, $this> */
    public function ebook(): BelongsTo
    {
        return $this->belongsTo(Ebook::class);
    }

    /** @return BelongsTo<Pembayaran, $this> */
    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class);
    }
}
