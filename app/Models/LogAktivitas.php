<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JenisAksi;
use App\Enums\TargetAksi;
use Database\Factories\LogAktivitasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $waktu
 * @property int|null $aktor_id
 * @property string $aktor_nama
 * @property JenisAksi $aksi
 * @property TargetAksi $target_tipe
 * @property string|null $target_id
 * @property string|null $target_label
 * @property string $deskripsi
 */
#[Fillable([
    'waktu', 'aktor_id', 'aktor_nama', 'aksi',
    'target_tipe', 'target_id', 'target_label', 'deskripsi',
])]
class LogAktivitas extends Model
{
    /** @use HasFactory<LogAktivitasFactory> */
    use HasFactory;

    protected $table = 'log_aktivitas';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
            'aksi' => JenisAksi::class,
            'target_tipe' => TargetAksi::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function aktor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aktor_id');
    }
}
