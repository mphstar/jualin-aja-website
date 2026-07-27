<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\JenisAksi;
use App\Enums\TargetAksi;
use App\Models\Ebook;
use App\Services\PencatatAktivitas;
use Illuminate\Support\Facades\Storage;

final readonly class HapusEbook
{
    public function __construct(private PencatatAktivitas $pencatat) {}

    public function __invoke(Ebook $ebook): void
    {
        $judul = $ebook->judul;
        $id = (string) $ebook->id;

        foreach ([$ebook->cover_path, $ebook->berkas_path] as $path) {
            if ($path !== null && $path !== '' && ! str_starts_with($path, 'http')) {
                Storage::disk('public')->delete($path);
            }
        }

        // Riwayat unduhan ikut terhapus lewat cascade di skema — angkanya tidak
        // berarti apa-apa lagi begitu ebook-nya tidak ada.
        $ebook->delete();

        $this->pencatat->catat(
            aksi: JenisAksi::EbookHapus,
            targetTipe: TargetAksi::Ebook,
            deskripsi: sprintf('Menghapus ebook "%s".', $judul),
            targetId: $id,
            targetLabel: $judul,
        );
    }
}
