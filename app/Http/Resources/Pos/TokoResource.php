<?php

declare(strict_types=1);

namespace App\Http\Resources\Pos;

use App\Models\PosUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Data toko yang tercetak di kepala struk.
 *
 * @mixin PosUser
 */
class TokoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'nama' => $this->nama_toko,
            'jenisUsaha' => $this->jenis_usaha->value,
            'jenisUsahaLabel' => $this->jenis_usaha->label(),
            'alamat' => $this->alamat ?? '',
            'kota' => $this->kota,
            'telepon' => $this->telepon,
        ];
    }
}
