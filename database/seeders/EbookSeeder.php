<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StatusEbook;
use App\Models\Ebook;
use App\Models\PosUser;
use App\Models\UnduhanEbook;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * 12 ebook (9 terbit, 3 draf) beserta riwayat unduhannya.
 *
 * Berkas PDF dan cover sengaja TIDAK ikut dibuat: seed hanya membangun
 * metadata. Unggahan sungguhan diuji lewat form /resep, dan menaruh berkas
 * palsu di storage hanya menyulitkan pembersihan.
 */
class EbookSeeder extends Seeder
{
    public function run(): void
    {
        $acak = new Acak(20260726);
        $sekarang = CarbonImmutable::now();
        $sumber = KolamData::ebook();
        $jumlahDraf = 3;

        foreach ($sumber as $i => $data) {
            $draf = $i >= count($sumber) - $jumlahDraf;
            $dibuat = $sekarang->subDays($acak->bulat(20, 400));

            Ebook::query()->create([
                'judul' => $data['judul'],
                'slug' => Str::slug($data['judul']),
                'jenis' => $data['jenis'],
                'kategori' => $data['kategori'],
                'kategori_prompt' => $data['kategoriPrompt'],
                'deskripsi' => $data['deskripsi'],
                'nama_berkas' => Str::slug($data['judul']).'.pdf',
                'ukuran_berkas_bytes' => $acak->bulat(3, 24) * 1024 * 1024,
                'jumlah_halaman' => $acak->bulat(28, 140),
                'status' => $draf ? StatusEbook::Draf : StatusEbook::Terbit,
                'tanggal_terbit' => $draf ? null : $dibuat->addDays($acak->bulat(1, 10)),
                'created_at' => $dibuat,
                'updated_at' => $dibuat,
            ]);
        }

        $this->buatUnduhan($acak, $sekarang);
    }

    private function buatUnduhan(Acak $acak, CarbonImmutable $sekarang): void
    {
        // Hanya toko yang tidak ditangguhkan yang bisa mengunduh (PRD §4.3).
        $idBerhak = array_values(PosUser::query()->where('ditangguhkan', false)->pluck('id')->all());
        $baris = [];

        foreach (Ebook::query()->where('status', StatusEbook::Terbit->value)->get() as $ebook) {
            $banyak = $acak->bulat(12, 45);

            for ($i = 0; $i < $banyak; $i++) {
                $tanggal = $this->padaJamWajar($acak, $sekarang->subDays($acak->bulat(0, 120)), $sekarang);
                $baris[] = [
                    'ebook_id' => $ebook->id,
                    'pos_user_id' => $acak->pilih($idBerhak),
                    'tanggal' => $tanggal,
                    'created_at' => $tanggal,
                    'updated_at' => $tanggal,
                ];
            }

            // Denormalisasi jumlah unduhan (PRD §6) — tabel katalog membacanya
            // langsung alih-alih menghitung ulang tiap kali dibuka.
            $ebook->update(['jumlah_unduhan' => $banyak]);
        }

        foreach (array_chunk($baris, 200) as $potongan) {
            UnduhanEbook::query()->insert($potongan);
        }
    }

    /**
     * Sebar peristiwa ke jam kerja yang wajar.
     *
     * Tanpa ini SEMUA unduhan tercatat pada jam yang persis sama — langsung
     * terlihat palsu begitu kolom waktu ditampilkan.
     */
    private function padaJamWajar(Acak $acak, CarbonImmutable $tanggal, CarbonImmutable $sekarang): CarbonImmutable
    {
        $waktu = $tanggal->setTime($acak->bulat(7, 22), $acak->bulat(0, 59), $acak->bulat(0, 59));

        return $waktu->greaterThan($sekarang) ? $sekarang : $waktu;
    }
}
