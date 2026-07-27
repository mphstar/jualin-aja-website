<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\UnduhanEbook;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Padanan tipe `UnduhanRingkas`. Butuh relasi `posUser` dan `ebook`
 * sudah di-eager-load.
 *
 * @mixin UnduhanEbook
 */
class UnduhanRingkasResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'ebookId' => (string) $this->ebook_id,
            'userId' => (string) $this->pos_user_id,
            'tanggal' => $this->tanggal->toISOString(),
            'namaUser' => $this->posUser->nama,
            'namaToko' => $this->posUser->nama_toko,
            'judulEbook' => $this->ebook->judul,
        ];
    }
}
