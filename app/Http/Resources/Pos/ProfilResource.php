<?php

declare(strict_types=1);

namespace App\Http\Resources\Pos;

use App\Models\PosUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Orang yang memakai aplikasi — bukan tokonya.
 *
 * `peran` masih tetap "Pemilik" untuk semua akun. Ia dikirim sebagai data,
 * bukan ditulis mati di layar, supaya penambahan peran kedua nanti tidak
 * menuntut merilis ulang aplikasi.
 *
 * @mixin PosUser
 */
class ProfilResource extends JsonResource
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
            'peran' => 'Pemilik',
        ];
    }
}
