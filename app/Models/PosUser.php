<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DurasiPaket;
use App\Enums\JenisUsaha;
use App\Enums\StatusLangganan;
use App\Enums\SumberLangganan;
use App\Support\KondisiLangganan;
use Carbon\CarbonInterface;
use Database\Factories\PosUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Pemilik toko — tenant yang memakai aplikasi POS mobile.
 *
 * @property int $id
 * @property string $nama
 * @property string $email
 * @property string $telepon
 * @property string|null $avatar_url
 * @property string $nama_toko
 * @property JenisUsaha $jenis_usaha
 * @property string $kota
 * @property Carbon $tanggal_daftar
 * @property bool $ditangguhkan
 * @property string|null $alasan_penangguhan
 * @property int|null $langganan_berlaku_id
 * @property Carbon|null $langganan_berakhir_pada
 * @property DurasiPaket|null $langganan_durasi
 * @property SumberLangganan|null $langganan_sumber
 */
#[Fillable([
    'nama', 'email', 'telepon', 'avatar_url', 'nama_toko',
    'jenis_usaha', 'kota', 'tanggal_daftar', 'ditangguhkan',
    'alasan_penangguhan', 'password',
])]
#[Hidden(['password'])]
class PosUser extends Model
{
    /** @use HasFactory<PosUserFactory> */
    use HasFactory;

    protected $table = 'pos_users';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'jenis_usaha' => JenisUsaha::class,
            'tanggal_daftar' => 'datetime',
            'ditangguhkan' => 'boolean',
            'langganan_berakhir_pada' => 'datetime',
            'langganan_durasi' => DurasiPaket::class,
            'langganan_sumber' => SumberLangganan::class,
            'password' => 'hashed',
        ];
    }

    /** @return HasMany<Langganan, $this> */
    public function langganan(): HasMany
    {
        return $this->hasMany(Langganan::class);
    }

    /**
     * Siklus yang sedang dipakai: tanggal berakhirnya paling jauh ke depan.
     * Dibaca lewat kolom cache, bukan subkueri — lihat migrasi pos_users.
     *
     * @return BelongsTo<Langganan, $this>
     */
    public function langgananBerlaku(): BelongsTo
    {
        return $this->belongsTo(Langganan::class, 'langganan_berlaku_id');
    }

    /** @return HasMany<Pembayaran, $this> */
    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    /** @return HasMany<UnduhanEbook, $this> */
    public function unduhan(): HasMany
    {
        return $this->hasMany(UnduhanEbook::class);
    }

    public function status(?CarbonInterface $sekarang = null): StatusLangganan
    {
        return KondisiLangganan::status(
            $this->langganan_berakhir_pada,
            $this->langganan_sumber,
            $this->ditangguhkan,
            $sekarang,
        );
    }

    public function sisaHari(?CarbonInterface $sekarang = null): int
    {
        if ($this->langganan_berakhir_pada === null) {
            return 0;
        }

        return KondisiLangganan::sisaHari($this->langganan_berakhir_pada, $sekarang);
    }

    /**
     * Segarkan kolom ringkasan langganan.
     *
     * Satu-satunya penulis kolom cache di tabel ini. Dipanggil dari
     * LanggananObserver, bukan dari controller — kalau boleh dipanggil dari
     * mana saja, satu jalur yang terlewat membuat filter status berbohong.
     */
    public function segarkanRingkasanLangganan(): void
    {
        $berlaku = $this->langganan()
            ->orderByDesc('tanggal_berakhir')
            ->orderByDesc('id')
            ->first();

        $this->forceFill([
            'langganan_berlaku_id' => $berlaku?->id,
            'langganan_berakhir_pada' => $berlaku?->tanggal_berakhir,
            'langganan_durasi' => $berlaku?->durasi,
            'langganan_sumber' => $berlaku?->sumber,
        ])->saveQuietly();
    }
}
