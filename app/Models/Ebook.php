<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\KategoriEbook;
use App\Enums\StatusEbook;
use Database\Factories\EbookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $judul
 * @property string $slug
 * @property KategoriEbook $kategori
 * @property string $deskripsi
 * @property string|null $cover_path
 * @property string|null $berkas_path
 * @property string|null $nama_berkas
 * @property int|null $ukuran_berkas_bytes
 * @property int|null $jumlah_halaman
 * @property StatusEbook $status
 * @property Carbon|null $tanggal_terbit
 * @property int $jumlah_unduhan
 * @property Carbon $created_at
 */
#[Fillable([
    'judul', 'slug', 'kategori', 'deskripsi', 'cover_path', 'berkas_path',
    'nama_berkas', 'ukuran_berkas_bytes', 'jumlah_halaman', 'status',
    'tanggal_terbit', 'jumlah_unduhan',
])]
class Ebook extends Model
{
    /** @use HasFactory<EbookFactory> */
    use HasFactory;

    protected $table = 'ebook';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kategori' => KategoriEbook::class,
            'status' => StatusEbook::class,
            'tanggal_terbit' => 'datetime',
            'ukuran_berkas_bytes' => 'integer',
            'jumlah_halaman' => 'integer',
            'jumlah_unduhan' => 'integer',
        ];
    }

    /** @return HasMany<UnduhanEbook, $this> */
    public function unduhan(): HasMany
    {
        return $this->hasMany(UnduhanEbook::class);
    }

    /**
     * URL publik cover. Path yang sudah berupa URL penuh (dipakai data seed
     * yang menunjuk gambar contoh) diteruskan apa adanya.
     */
    public function coverUrl(): ?string
    {
        return self::keUrl($this->cover_path);
    }

    public function berkasUrl(): ?string
    {
        return self::keUrl($this->berkas_path);
    }

    private static function keUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
