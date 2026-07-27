<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\JenisAksi;
use App\Enums\KategoriEbook;
use App\Enums\StatusEbook;
use App\Enums\TargetAksi;
use App\Models\Ebook;
use App\Services\PencatatAktivitas;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Tambah atau ubah ebook (PRD §F5.4).
 *
 * Satu action untuk keduanya karena aturan berkasnya sama persis, dan
 * memisahkannya berarti menggandakan penanganan unggahan — tempat paling
 * mudah untuk lupa menghapus berkas lama.
 */
final readonly class SimpanEbook
{
    public function __construct(private PencatatAktivitas $pencatat) {}

    public function __invoke(
        ?Ebook $ebook,
        string $judul,
        KategoriEbook $kategori,
        string $deskripsi,
        StatusEbook $status,
        ?UploadedFile $cover = null,
        ?UploadedFile $berkas = null,
        ?int $jumlahHalaman = null,
    ): Ebook {
        $baru = $ebook === null;
        $statusLama = $ebook?->status;

        $atribut = [
            'judul' => $judul,
            'slug' => $this->slugUnik($judul, $ebook?->id),
            'kategori' => $kategori,
            'deskripsi' => $deskripsi,
            'status' => $status,
        ];

        if ($cover !== null) {
            $this->hapusBerkas($ebook?->cover_path);
            $atribut['cover_path'] = $cover->store('ebook/cover', 'public');
        }

        if ($berkas !== null) {
            $this->hapusBerkas($ebook?->berkas_path);
            $atribut['berkas_path'] = $berkas->store('ebook/berkas', 'public');
            $atribut['nama_berkas'] = $berkas->getClientOriginalName();
            $atribut['ukuran_berkas_bytes'] = $berkas->getSize();
        }

        if ($jumlahHalaman !== null) {
            $atribut['jumlah_halaman'] = $jumlahHalaman;
        }

        // Tanggal terbit dicatat sekali, saat pertama kali naik ke Terbit —
        // menerbitkan ulang draf lama tidak boleh menghapus jejak aslinya.
        if ($status === StatusEbook::Terbit && $ebook?->tanggal_terbit === null) {
            $atribut['tanggal_terbit'] = now();
        }

        if ($baru) {
            $ebook = Ebook::query()->create($atribut);
        } else {
            $ebook->update($atribut);
        }

        $this->catat($ebook, $baru, $statusLama);

        return $ebook;
    }

    private function catat(Ebook $ebook, bool $baru, ?StatusEbook $statusLama): void
    {
        [$aksi, $deskripsi] = match (true) {
            $baru && $ebook->status === StatusEbook::Terbit => [
                JenisAksi::EbookTerbitkan,
                sprintf('Menambahkan dan menerbitkan ebook "%s".', $ebook->judul),
            ],
            $baru => [
                JenisAksi::EbookTambah,
                sprintf('Menambahkan draf ebook "%s".', $ebook->judul),
            ],
            $statusLama !== StatusEbook::Terbit && $ebook->status === StatusEbook::Terbit => [
                JenisAksi::EbookTerbitkan,
                sprintf('Menerbitkan ebook "%s".', $ebook->judul),
            ],
            default => [
                JenisAksi::EbookUbah,
                sprintf('Mengubah ebook "%s".', $ebook->judul),
            ],
        };

        $this->pencatat->catat(
            aksi: $aksi,
            targetTipe: TargetAksi::Ebook,
            deskripsi: $deskripsi,
            targetId: (string) $ebook->id,
            targetLabel: $ebook->judul,
        );
    }

    /** Judul boleh sama; slug tidak — ia dipakai sebagai penanda publik. */
    private function slugUnik(string $judul, ?int $kecualiId): string
    {
        $dasar = Str::slug($judul);
        $slug = $dasar;
        $urutan = 2;

        while (Ebook::query()->where('slug', $slug)->when($kecualiId, fn ($q) => $q->whereKeyNot($kecualiId))->exists()) {
            $slug = $dasar.'-'.$urutan;
            $urutan++;
        }

        return $slug;
    }

    private function hapusBerkas(?string $path): void
    {
        if ($path !== null && $path !== '' && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }
}
