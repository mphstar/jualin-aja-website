<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DurasiPaket;
use App\Enums\StatusLangganan;
use App\Enums\SumberLangganan;
use App\Observers\LanggananObserver;
use App\Support\KondisiLangganan;
use Carbon\CarbonInterface;
use Database\Factories\LanggananFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Satu siklus langganan. Perpanjangan membuat baris baru, tidak menimpa
 * yang lama (PRD §F4.5).
 *
 * @property int $id
 * @property int $pos_user_id
 * @property DurasiPaket $durasi
 * @property SumberLangganan $sumber
 * @property Carbon $tanggal_mulai
 * @property Carbon $tanggal_berakhir
 * @property string|null $dibuat_oleh
 * @property string|null $catatan
 * @property-read PosUser $posUser
 */
#[Fillable([
    'pos_user_id', 'durasi', 'sumber', 'tanggal_mulai',
    'tanggal_berakhir', 'dibuat_oleh', 'catatan',
])]
#[ObservedBy([LanggananObserver::class])]
class Langganan extends Model
{
    /** @use HasFactory<LanggananFactory> */
    use HasFactory;

    protected $table = 'langganan';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'durasi' => DurasiPaket::class,
            'sumber' => SumberLangganan::class,
            'tanggal_mulai' => 'datetime',
            'tanggal_berakhir' => 'datetime',
        ];
    }

    /** @return BelongsTo<PosUser, $this> */
    public function posUser(): BelongsTo
    {
        return $this->belongsTo(PosUser::class);
    }

    /**
     * Status baris ini sendiri — bukan status pemiliknya. Baris riwayat lama
     * memang wajar berstatus KEDALUWARSA meski tokonya sedang aktif.
     */
    public function status(bool $ditangguhkan, ?CarbonInterface $sekarang = null): StatusLangganan
    {
        return KondisiLangganan::status(
            $this->tanggal_berakhir,
            $this->sumber,
            $ditangguhkan,
            $sekarang,
        );
    }

    public function sisaHari(?CarbonInterface $sekarang = null): int
    {
        return KondisiLangganan::sisaHari($this->tanggal_berakhir, $sekarang);
    }
}
