<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Pembayaran;
use Illuminate\Http\Request;

/**
 * Padanan tipe `PembayaranRingkas`. Butuh relasi `posUser` sudah di-eager-load.
 *
 * @mixin Pembayaran
 */
class PembayaranRingkasResource extends PembayaranResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'namaUser' => $this->posUser->nama,
            'namaToko' => $this->posUser->nama_toko,
            'avatarUrl' => $this->posUser->avatar_url,
        ];
    }
}
