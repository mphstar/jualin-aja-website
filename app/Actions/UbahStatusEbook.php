<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\JenisAksi;
use App\Enums\StatusEbook;
use App\Enums\TargetAksi;
use App\Models\Ebook;
use App\Services\PencatatAktivitas;

/** Sakelar cepat Draf ↔ Terbit dari tabel katalog (PRD §F5.5). */
final readonly class UbahStatusEbook
{
    public function __construct(private PencatatAktivitas $pencatat) {}

    public function __invoke(Ebook $ebook, StatusEbook $status): Ebook
    {
        $atribut = ['status' => $status];

        if ($status === StatusEbook::Terbit && $ebook->tanggal_terbit === null) {
            $atribut['tanggal_terbit'] = now();
        }

        $ebook->update($atribut);

        $this->pencatat->catat(
            aksi: $status === StatusEbook::Terbit
                ? JenisAksi::EbookTerbitkan
                : JenisAksi::EbookJadikanDraf,
            targetTipe: TargetAksi::Ebook,
            deskripsi: $status === StatusEbook::Terbit
                ? sprintf('Menerbitkan ebook "%s".', $ebook->judul)
                : sprintf('Mengembalikan ebook "%s" menjadi draf.', $ebook->judul),
            targetId: (string) $ebook->id,
            targetLabel: $ebook->judul,
        );

        return $ebook;
    }
}
