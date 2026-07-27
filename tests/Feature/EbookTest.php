<?php

declare(strict_types=1);

use App\Enums\JenisAksi;
use App\Enums\KategoriEbook;
use App\Enums\StatusEbook;
use App\Models\Ebook;
use App\Models\LogAktivitas;
use App\Models\PosUser;
use App\Models\UnduhanEbook;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    admin();
    Storage::fake('public');
});

it('menyaring katalog menurut kategori dan status', function (): void {
    Ebook::factory()->count(2)->create(['kategori' => KategoriEbook::Minuman]);
    Ebook::factory()->draf()->create(['kategori' => KategoriEbook::Bakery]);

    expect($this->getJson('/api/v1/ebook?kategori=MINUMAN')->assertOk()->json('total'))->toBe(2)
        ->and($this->getJson('/api/v1/ebook?status=DRAF')->assertOk()->json('total'))->toBe(1)
        ->and($this->getJson('/api/v1/ebook')->assertOk()->json('total'))->toBe(3);
});

it('menyimpan ebook baru beserta cover dan berkas PDF', function (): void {
    $respons = $this->post('/api/v1/ebook', [
        'judul' => 'Sambal & Saus Andalan',
        'kategori' => KategoriEbook::BumbuSaus->value,
        'deskripsi' => 'Sambal bawang, matah, ijo, dan saus pendamping yang tahan lama.',
        'status' => StatusEbook::Terbit->value,
        'jumlahHalaman' => 96,
        'cover' => UploadedFile::fake()->image('cover.jpg'),
        'berkas' => UploadedFile::fake()->create('sambal.pdf', 512, 'application/pdf'),
    ])->assertCreated();

    $ebook = Ebook::query()->sole();

    expect($ebook->slug)->toBe('sambal-saus-andalan')
        ->and($ebook->nama_berkas)->toBe('sambal.pdf')
        ->and($ebook->tanggal_terbit)->not->toBeNull()
        ->and($respons->json('coverUrl'))->toContain('/storage/');

    Storage::disk('public')->assertExists($ebook->cover_path);
    Storage::disk('public')->assertExists($ebook->berkas_path);

    expect(LogAktivitas::query()->where('aksi', JenisAksi::EbookTerbitkan->value)->exists())->toBeTrue();
});

it('menolak berkas yang bukan PDF', function (): void {
    $this->post('/api/v1/ebook', [
        'judul' => 'Judul Uji',
        'kategori' => KategoriEbook::Snack->value,
        'deskripsi' => 'Deskripsi yang cukup panjang untuk lolos validasi.',
        'status' => StatusEbook::Draf->value,
        'berkas' => UploadedFile::fake()->create('bukan.docx', 10),
    ])->assertStatus(422)->assertJsonValidationErrors(['berkas']);
});

it('mengganti berkas lama saat ebook diperbarui', function (): void {
    $ebook = Ebook::factory()->create([
        'cover_path' => UploadedFile::fake()->image('lama.jpg')->store('ebook/cover', 'public'),
    ]);
    $pathLama = $ebook->cover_path;

    $this->post("/api/v1/ebook/{$ebook->id}", [
        'judul' => $ebook->judul,
        'kategori' => $ebook->kategori->value,
        'deskripsi' => $ebook->deskripsi,
        'status' => $ebook->status->value,
        'cover' => UploadedFile::fake()->image('baru.jpg'),
    ])->assertOk();

    // Berkas lama tidak boleh menumpuk jadi sampah di disk.
    Storage::disk('public')->assertMissing($pathLama);
    Storage::disk('public')->assertExists($ebook->refresh()->cover_path);
});

it('mempertahankan berkas lama bila unggahan baru tidak disertakan', function (): void {
    $ebook = Ebook::factory()->create([
        'berkas_path' => UploadedFile::fake()->create('asli.pdf', 20, 'application/pdf')->store('ebook/berkas', 'public'),
        'nama_berkas' => 'asli.pdf',
    ]);
    $pathAsli = $ebook->berkas_path;

    $this->post("/api/v1/ebook/{$ebook->id}", [
        'judul' => 'Judul Diperbarui',
        'kategori' => $ebook->kategori->value,
        'deskripsi' => $ebook->deskripsi,
        'status' => $ebook->status->value,
    ])->assertOk();

    expect($ebook->refresh()->berkas_path)->toBe($pathAsli)
        ->and($ebook->nama_berkas)->toBe('asli.pdf')
        ->and($ebook->judul)->toBe('Judul Diperbarui');
});

it('mencatat tanggal terbit sekali saja', function (): void {
    $ebook = Ebook::factory()->draf()->create();

    $this->patchJson("/api/v1/ebook/{$ebook->id}/status", ['status' => StatusEbook::Terbit->value])->assertOk();
    $pertama = $ebook->refresh()->tanggal_terbit;

    $this->patchJson("/api/v1/ebook/{$ebook->id}/status", ['status' => StatusEbook::Draf->value])->assertOk();
    $this->patchJson("/api/v1/ebook/{$ebook->id}/status", ['status' => StatusEbook::Terbit->value])->assertOk();

    // Menerbitkan ulang tidak menghapus jejak penerbitan pertama.
    expect($ebook->refresh()->tanggal_terbit->toIso8601String())->toBe($pertama->toIso8601String())
        ->and(LogAktivitas::query()->where('aksi', JenisAksi::EbookJadikanDraf->value)->count())->toBe(1);
});

it('membuat slug unik meski judulnya sama', function (): void {
    foreach (range(1, 2) as $ke) {
        $this->post('/api/v1/ebook', [
            'judul' => 'Bumbu Dasar Serbaguna',
            'kategori' => KategoriEbook::BumbuSaus->value,
            'deskripsi' => 'Bumbu dasar merah, putih, dan kuning untuk puluhan menu.',
            'status' => StatusEbook::Draf->value,
        ])->assertCreated();
    }

    expect(Ebook::query()->pluck('slug')->all())
        ->toBe(['bumbu-dasar-serbaguna', 'bumbu-dasar-serbaguna-2']);
});

it('menyusun detail ebook dengan deret unduhan 30 hari', function (): void {
    $ebook = Ebook::factory()->create();
    $posUser = PosUser::factory()->create(['nama' => 'Budi Santoso']);

    UnduhanEbook::factory()->count(2)->create([
        'ebook_id' => $ebook->id,
        'pos_user_id' => $posUser->id,
        'tanggal' => now()->subDays(2),
    ]);

    $respons = $this->getJson("/api/v1/ebook/{$ebook->id}")->assertOk();

    expect($respons->json('unduhan'))->toHaveCount(2)
        ->and($respons->json('unduhan.0.namaUser'))->toBe('Budi Santoso')
        ->and($respons->json('deret30Hari'))->toHaveCount(30)
        ->and(array_sum(array_column($respons->json('deret30Hari'), 'nilai')))->toBe(2);
});

it('menghapus ebook beserta berkasnya', function (): void {
    $ebook = Ebook::factory()->create([
        'judul' => 'Kue Kering Musim Lebaran',
        'cover_path' => UploadedFile::fake()->image('cover.jpg')->store('ebook/cover', 'public'),
    ]);
    $path = $ebook->cover_path;

    $this->deleteJson("/api/v1/ebook/{$ebook->id}")->assertNoContent();

    Storage::disk('public')->assertMissing($path);

    expect(Ebook::query()->count())->toBe(0)
        ->and(LogAktivitas::query()->where('aksi', JenisAksi::EbookHapus->value)->sole()->target_label)
        ->toBe('Kue Kering Musim Lebaran');
});

it('mengembalikan 404 untuk ebook yang tidak ada', function (): void {
    $this->getJson('/api/v1/ebook/99999')->assertNotFound();
});
