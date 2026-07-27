<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Penyimpan kunci-nilai untuk pengaturan aplikasi.
 *
 * @property string $kunci
 * @property mixed $nilai
 */
#[Fillable(['kunci', 'nilai'])]
class Pengaturan extends Model
{
    public const string KUNCI_HARGA_PAKET = 'harga_paket';

    protected $table = 'pengaturan';

    protected $primaryKey = 'kunci';

    protected $keyType = 'string';

    public $incrementing = false;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['nilai' => 'array'];
    }

    public static function ambil(string $kunci, mixed $bawaan = null): mixed
    {
        $baris = self::query()->find($kunci);

        return $baris === null ? $bawaan : $baris->nilai;
    }

    public static function simpan(string $kunci, mixed $nilai): void
    {
        self::query()->updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
    }
}
