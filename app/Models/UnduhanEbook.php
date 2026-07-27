<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UnduhanEbookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ebook_id
 * @property int $pos_user_id
 * @property Carbon $tanggal
 * @property-read Ebook $ebook
 * @property-read PosUser $posUser
 */
#[Fillable(['ebook_id', 'pos_user_id', 'tanggal'])]
class UnduhanEbook extends Model
{
    /** @use HasFactory<UnduhanEbookFactory> */
    use HasFactory;

    protected $table = 'unduhan_ebook';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['tanggal' => 'datetime'];
    }

    /** @return BelongsTo<Ebook, $this> */
    public function ebook(): BelongsTo
    {
        return $this->belongsTo(Ebook::class);
    }

    /** @return BelongsTo<PosUser, $this> */
    public function posUser(): BelongsTo
    {
        return $this->belongsTo(PosUser::class);
    }
}
