<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Langganan;
use Illuminate\Http\Request;

/**
 * Padanan tipe `LanggananRingkas` — satu baris tabel /langganan.
 *
 * Status di sini milik BARIS-nya sendiri, bukan milik tokonya: siklus lama
 * memang wajar berstatus kedaluwarsa meski toko itu sedang aktif lewat
 * perpanjangan terbaru. `berlaku` yang membedakan keduanya.
 *
 * Butuh relasi `posUser` sudah di-eager-load.
 *
 * @mixin Langganan
 */
class LanggananRingkasResource extends LanggananResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $posUser = $this->posUser;

        return [
            ...parent::toArray($request),
            'namaUser' => $posUser->nama,
            'namaToko' => $posUser->nama_toko,
            'avatarUrl' => $posUser->avatar_url,
            'status' => $this->status($posUser->ditangguhkan)->value,
            'sisaHari' => $this->sisaHari(),
            'berlaku' => $posUser->langganan_berlaku_id === $this->id,
        ];
    }
}
