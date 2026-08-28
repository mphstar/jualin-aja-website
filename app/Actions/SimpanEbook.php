<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\JenisAksi;
use App\Enums\JenisKonten;
use App\Enums\KategoriEbook;
use App\Enums\KategoriPrompt;
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
        JenisKonten $jenis,
        string $judul,
        ?KategoriEbook $kategori,
        ?KategoriPrompt $kategoriPrompt,
        string $deskripsi,
        StatusEbook $status,
        ?UploadedFile $cover = null,
        ?UploadedFile $berkas = null,
        ?int $jumlahHalaman = null,
    ): Ebook {
        $baru = $ebook === null;
        $statusLama = $ebook?->status;

        $atribut = [
            'jenis' => $jenis,
            'judul' => $judul,
            'slug' => $this->slugUnik($judul, $ebook?->id),
            'kategori' => $kategori,
            'kategori_prompt' => $kategoriPrompt,
            'deskripsi' => $deskripsi,
            'status' => $status,
        ];

        if ($cover !== null) {
            $this->hapusBerkas($ebook?->cover_path);
            $atribut['cover_path'] = $this->simpan($cover, 'ebook/cover');
        }

        if ($berkas !== null) {
            $this->hapusBerkas($ebook?->berkas_path);
            $atribut['berkas_path'] = $this->simpan($berkas, 'ebook/berkas');
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

    /**
     * Simpan unggahan ke disk publik. Kalau write gagal (mis. folder tidak
     * writable), `store()` mengembalikan false — menyimpannya ke atribut akan
     * meracuni `berkas_path` dengan boolean dan membuat resource/`unduh`
     * melempar TypeError (500). Guard ini mengubahnya jadi pesan validasi yang
     * bisa dipahami, bukan layar server error.
     */
    private function simpan(UploadedFile $file, string $direktori): string
    {
        $path = $file->store($direktori, 'public');

        if (! is_string($path) || $path === '') {
            abort(422, 'Gagal menyimpan berkas. Periksa izin folder penyimpanan.');
        }

        return $path;
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
