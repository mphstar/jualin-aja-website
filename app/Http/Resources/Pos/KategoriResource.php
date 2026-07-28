<?php

declare(strict_types=1);

namespace App\Http\Resources\Pos;

use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Padanan `Kategori` di aplikasi Flutter.
 *
 * `ikon` dikirim sebagai NAMA ikon Material, bukan kode titik atau berkas —
 * aplikasi memetakannya ke IconData lewat daftar tertutup yang sama dengan
 * App\Enums\IkonKategori, jadi ikon yang tidak dikenali tidak akan pernah
 * sampai ke layar.
 *
 * @mixin Kategori
 */
class KategoriResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'nama' => $this->nama,
            'ikon' => $this->ikon->value,
            'urutan' => $this->urutan,
        ];
    }
}
