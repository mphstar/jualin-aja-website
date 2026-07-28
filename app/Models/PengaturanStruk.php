<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LebarKertas;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Apa yang tercetak di struk sebuah toko.
 *
 * @property int $id
 * @property int $pos_user_id
 * @property string $kepala
 * @property string $kaki
 * @property bool $tampilkan_alamat
 * @property bool $tampilkan_telepon
 * @property bool $tampilkan_nama_kasir
 * @property LebarKertas $lebar
 * @property-read PosUser $posUser
 */
#[Fillable([
    'pos_user_id', 'kepala', 'kaki', 'tampilkan_alamat',
    'tampilkan_telepon', 'tampilkan_nama_kasir', 'lebar',
])]
class PengaturanStruk extends Model
{
    protected $table = 'pengaturan_struk';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tampilkan_alamat' => 'boolean',
            'tampilkan_telepon' => 'boolean',
            'tampilkan_nama_kasir' => 'boolean',
            'lebar' => LebarKertas::class,
        ];
    }

    /** @return BelongsTo<PosUser, $this> */
    public function posUser(): BelongsTo
    {
        return $this->belongsTo(PosUser::class);
    }

    /**
     * Nilai awal yang sudah layak dipakai tanpa disetel apa pun.
     *
     * Struk yang keluar dari kotak harus tetap pantas diberikan ke pembeli —
     * kepala dan kaki kosong membuat kertasnya terlihat seperti hasil cetakan
     * yang gagal, bukan struk toko.
     *
     * @return array<string, mixed>
     */
    public static function bawaan(): array
    {
        return [
            'kepala' => 'Terima kasih sudah mampir',
            'kaki' => 'Barang yang sudah dibeli tidak dapat ditukar',
            'tampilkan_alamat' => true,
            'tampilkan_telepon' => true,
            'tampilkan_nama_kasir' => false,
            'lebar' => LebarKertas::Mm58,
        ];
    }
}
