<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Ebook;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Padanan tipe `Ebook`.
 *
 * Database menyimpan PATH, respons mengirim URL — supaya pindah disk atau
 * domain nanti tidak menuntut penulisan ulang baris yang sudah ada.
 *
 * @mixin Ebook
 */
class EbookResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'jenis' => $this->jenis->value,
            'judul' => $this->judul,
            'slug' => $this->slug,
            'kategori' => $this->kategori?->value,
            'kategoriPrompt' => $this->kategori_prompt?->value,
            'deskripsi' => $this->deskripsi,
            'coverUrl' => $this->coverUrl(),
            'fileUrl' => $this->berkasUrl(),
            'namaFile' => $this->nama_berkas,
            'ukuranFileBytes' => $this->ukuran_berkas_bytes,
            'jumlahHalaman' => $this->jumlah_halaman,
            'status' => $this->status->value,
            'tanggalDibuat' => $this->created_at->toISOString(),
            'tanggalTerbit' => $this->tanggal_terbit?->toISOString(),
            'jumlahUnduhan' => $this->jumlah_unduhan,
        ];
    }
}
