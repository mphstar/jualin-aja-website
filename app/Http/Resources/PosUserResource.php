<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PosUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Padanan tipe `PosUser`.
 *
 * @mixin PosUser
 */
class PosUserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'nama' => $this->nama,
            'email' => $this->email,
            'telepon' => $this->telepon,
            'avatarUrl' => $this->avatar_url,
            'namaToko' => $this->nama_toko,
            'jenisUsaha' => $this->jenis_usaha->value,
            'kota' => $this->kota,
            'tanggalDaftar' => $this->tanggal_daftar->toISOString(),
            'ditangguhkan' => $this->ditangguhkan,
            'alasanPenangguhan' => $this->alasan_penangguhan,
        ];
    }
}
