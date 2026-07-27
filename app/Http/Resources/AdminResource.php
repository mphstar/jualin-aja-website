<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Padanan tipe `AdminUser` di resources/js/types/index.ts.
 *
 * Nama kunci sengaja camelCase Bahasa Indonesia, bukan kolom mentah: bentuk
 * inilah yang sudah dikonsumsi seluruh store dan komponen, jadi tidak ada
 * lapisan pemetaan yang perlu ditulis (dan dijaga) di sisi frontend.
 *
 * @mixin User
 */
class AdminResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'nama' => $this->name,
            'email' => $this->email,
            'avatarUrl' => $this->avatar_url,
            'terakhirMasuk' => ($this->terakhir_masuk ?? $this->created_at)?->toISOString(),
        ];
    }
}
