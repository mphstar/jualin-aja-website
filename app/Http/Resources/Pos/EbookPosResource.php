<?php

declare(strict_types=1);

namespace App\Http\Resources\Pos;

use App\Models\Ebook;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ebook resep dari sudut pandang pemilik toko.
 *
 * Field `terbuka`, `statusAkses`, dan `bisaKlaim` menentukan tombol aksi
 * di aplikasi mobile: klaim jatah langganan, beli satuan, atau langsung buka.
 *
 * @mixin Ebook
 */
class EbookPosResource extends JsonResource
{
    /**
     * @param  bool  $bolehUnduh  Sudah punya akses (klaim atau beli).
     * @param  bool  $bisaKlaim  Jatah klaim untuk jenis ini masih tersedia.
     */
    public function __construct(
        Ebook $ebook,
        private readonly bool $bolehUnduh,
        private readonly bool $bisaKlaim = false,
    ) {
        parent::__construct($ebook);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $terbuka = $this->bolehUnduh;

        return [
            'id' => (string) $this->id,
            'jenis' => $this->jenis->value,
            'judul' => $this->judul,
            'kategori' => $this->kategori?->value,
            'kategoriLabel' => $this->kategori?->label(),
            'kategoriPrompt' => $this->kategori_prompt?->value,
            'kategoriPromptLabel' => $this->kategori_prompt?->label(),
            'deskripsi' => $this->deskripsi,
            'coverUrl' => $this->urlBerkas($this->cover_path, $request),
            'jumlahHalaman' => $this->jumlah_halaman,
            'ukuranMb' => $this->ukuran_berkas_bytes === null
                ? null
                : round($this->ukuran_berkas_bytes / 1_048_576, 1),
            'harga' => $this->harga,
            'terbuka' => $terbuka,
            'statusAkses' => $this->hitungStatusAkses($terbuka),
            'bisaKlaim' => $this->bisaKlaim,
            'fileUrl' => $terbuka
                ? $this->urlBerkas($this->berkas_path, $request)
                : null,
        ];
    }

    private function hitungStatusAkses(bool $terbuka): string
    {
        if ($terbuka) {
            return 'TERBUKA';
        }

        if ($this->bisaKlaim) {
            return 'BISA_KLAIM';
        }

        return 'TERKUNCI';
    }

    /**
     * URL berkas relatif terhadap host yang mengirim permintaan.
     *
     * `Storage::disk('public')->url()` memakai `APP_URL` (= localhost:8000),
     * yang benar untuk panel admin tapi salah bagi aplikasi POS yang datang
     * lewat ngrok: `localhost` di perangkat itu menunjuk ke perangkatnya
     * sendiri, jadi tautan unduhan tak pernah sampai. Memakai `$request->root()`
     * membuat tautan selalu cocok dengan host yang dipakai aplikasi memanggil.
     */
    private function urlBerkas(mixed $path, Request $request): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim((string) $request->root(), '/').'/uploads/'.ltrim($path, '/');
    }
}
