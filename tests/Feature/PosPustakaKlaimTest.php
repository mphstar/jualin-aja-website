<?php

declare(strict_types=1);

use App\Actions\TandaiPembayaranLunas;
use App\Enums\DurasiPaket;
use App\Enums\SumberLangganan;
use App\Models\AksesPustaka;
use App\Models\Ebook;
use App\Models\Langganan;
use App\Models\Pembayaran;
use App\Models\PosUser;
use Carbon\CarbonImmutable;

/**
 * Klaim jatah langganan — 1 Resep + 1 Prompt gratis per SIKLUS langganan.
 *
 * Endpoint ini sebelumnya tidak punya tes sama sekali, dan aturannya pun
 * menghitung seumur akun: perpanjangan tidak pernah membuka jatah baru.
 */

/**
 * Toko berlangganan aktif LENGKAP dengan baris `langganan` siklusnya.
 *
 * `PosUserFactory::berlangganan()` hanya mengisi kolom ringkasan di
 * `pos_users`. Jatah klaim dihitung dari `created_at` baris `langganan`, jadi
 * tanpa baris itu patokannya null dan `sudahKlaimJenis()` akan selalu false —
 * tesnya lolos tanpa pernah menguji apa pun.
 */
function tokoSiklus(int $sisaHari = 20): PosUser
{
    $toko = PosUser::factory()->bisaMasuk()->create();

    Langganan::factory()->create([
        'pos_user_id' => $toko->id,
        'durasi' => DurasiPaket::Bulanan,
        'sumber' => SumberLangganan::Pembelian,
        'tanggal_mulai' => CarbonImmutable::now()->subDays(10),
        'tanggal_berakhir' => CarbonImmutable::now()->addDays($sisaHari),
    ]);

    return $toko->refresh();
}

/**
 * Lunasi invoice langganan — jalur yang persis sama dengan webhook Mayar.
 *
 * Siklus baru hanya lahir lewat sini, jadi inilah yang harus dipakai untuk
 * membuktikan perpanjangan membuka jatah klaim berikutnya.
 */
function lunasiLangganan(PosUser $toko, DurasiPaket $durasi = DurasiPaket::Bulanan): Pembayaran
{
    $pembayaran = Pembayaran::factory()->menunggu()->create([
        'pos_user_id' => $toko->id,
        'tipe' => Pembayaran::TIPE_LANGGANAN,
        'ebook_id' => null,
        'durasi' => $durasi,
    ]);

    return app(TandaiPembayaranLunas::class)($pembayaran);
}

/** Status `statusAkses` sebuah ebook pada jawaban `GET resep`. */
function statusAkses(int $ebookId): string
{
    $daftar = test()->getJson(route('api.mobile.resep.index'))->assertSuccessful()->json();

    return collect($daftar)->firstWhere('id', (string) $ebookId)['statusAkses'];
}

/** `jumlahUnduhan` sebuah ebook pada jawaban `GET resep`. */
function jumlahBuka(int $ebookId): int
{
    $daftar = test()->getJson(route('api.mobile.resep.index'))->assertSuccessful()->json();

    return (int) collect($daftar)->firstWhere('id', (string) $ebookId)['jumlahUnduhan'];
}

it('menolak klaim dari akun tanpa langganan berbayar', function (): void {
    $ebook = Ebook::factory()->create();

    $this->actingAs(PosUser::factory()->bisaMasuk()->create(), 'pos')
        ->postJson(route('api.mobile.resep.klaim', $ebook))
        ->assertStatus(402)
        ->assertJsonPath('kode', 'LANGGANAN_DIBUTUHKAN');

    expect(AksesPustaka::query()->count())->toBe(0);
});

it('membuka konten yang diklaim tanpa menyentuh jatah jenis lain', function (): void {
    $toko = tokoSiklus();
    $resep = Ebook::factory()->create();
    $prompt = Ebook::factory()->prompt()->create();

    $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.resep.klaim', $resep))
        ->assertSuccessful()
        ->assertJsonPath('terbuka', true);

    expect(AksesPustaka::query()
        ->where('pos_user_id', $toko->id)
        ->where('ebook_id', $resep->id)
        ->value('tipe_akses'))
        ->toBe(AksesPustaka::TIPE_KLAIM_LANGGANAN);

    $this->actingAs($toko->fresh(), 'pos');

    expect(statusAkses($resep->id))->toBe('TERBUKA')
        ->and(statusAkses($prompt->id))->toBe('BISA_KLAIM');
});

it('menolak klaim kedua untuk jenis yang sama, tapi tidak untuk jenis lain', function (): void {
    $toko = tokoSiklus();
    $resepA = Ebook::factory()->create();
    $resepB = Ebook::factory()->create();
    $prompt = Ebook::factory()->prompt()->create();

    $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.resep.klaim', $resepA))
        ->assertSuccessful();

    $this->actingAs($toko->fresh(), 'pos')
        ->postJson(route('api.mobile.resep.klaim', $resepB))
        ->assertStatus(409)
        ->assertJsonPath('kode', 'JATAH_KLAIM_HABIS');

    // Jatah Prompt adalah jatah tersendiri — 1 per JENIS, bukan 1 total.
    $this->actingAs($toko->fresh(), 'pos')
        ->postJson(route('api.mobile.resep.klaim', $prompt))
        ->assertSuccessful();

    expect(AksesPustaka::query()->where('pos_user_id', $toko->id)->count())->toBe(2);
});

it('membuka jatah baru begitu invoice langganan dilunasi', function (): void {
    // Inti perubahan: aturan lama menghitung seumur akun, jadi tes ini merah
    // di sana — klaim Resep kedua tetap ditolak walau siklusnya sudah baru.
    $beku = CarbonImmutable::parse('2026-09-14 20:00:00', 'Asia/Jakarta');
    $this->travelTo($beku);

    $toko = tokoSiklus();
    $resepA = Ebook::factory()->create();
    $resepB = Ebook::factory()->create();

    $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.resep.klaim', $resepA))
        ->assertSuccessful();

    $this->actingAs($toko->fresh(), 'pos')
        ->postJson(route('api.mobile.resep.klaim', $resepB))
        ->assertStatus(409)
        ->assertJsonPath('kode', 'JATAH_KLAIM_HABIS');

    // Perpanjangan lebih awal menyambung dari akhir siklus lama, jadi
    // `tanggal_mulai` siklus baru justru ada di masa depan. Jatahnya harus
    // tetap terbuka sekarang juga.
    $this->travelTo($beku->addDays(5));
    lunasiLangganan($toko);

    $this->actingAs($toko->fresh(), 'pos')
        ->postJson(route('api.mobile.resep.klaim', $resepB))
        ->assertSuccessful()
        ->assertJsonPath('terbuka', true);
});

it('menjawab klaim ulang ebook siklus lama dengan pesan, bukan galat 500', function (): void {
    // Baris `akses_pustaka` tidak pernah dihapus, sedangkan indeks unik
    // (pos_user_id, ebook_id) menolak baris kedua. Jalur ini harus berhenti di
    // pemeriksaan akses, tidak boleh sampai menyentuh INSERT.
    $beku = CarbonImmutable::parse('2026-09-14 20:00:00', 'Asia/Jakarta');
    $this->travelTo($beku);

    $toko = tokoSiklus(sisaHari: 5);
    $resepA = Ebook::factory()->create();
    $resepB = Ebook::factory()->create();

    $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.resep.klaim', $resepA))
        ->assertSuccessful();

    $this->travelTo($beku->addDays(10));
    lunasiLangganan($toko);

    $this->actingAs($toko->fresh(), 'pos')
        ->postJson(route('api.mobile.resep.klaim', $resepA))
        ->assertStatus(409)
        ->assertJsonPath('kode', 'SUDAH_PUNYA_AKSES');

    // Dan jatah siklus baru belum ikut terbakar oleh percobaan itu.
    $this->actingAs($toko->fresh(), 'pos')
        ->postJson(route('api.mobile.resep.klaim', $resepB))
        ->assertSuccessful();

    expect(AksesPustaka::query()->where('pos_user_id', $toko->id)->count())->toBe(2);
});

it('menolak klaim konten yang belum terbit', function (): void {
    $ebook = Ebook::factory()->draf()->create();

    $this->actingAs(tokoSiklus(), 'pos')
        ->postJson(route('api.mobile.resep.klaim', $ebook))
        ->assertNotFound();

    expect(AksesPustaka::query()->count())->toBe(0);
});

it('katalog Pustaka membawa jumlah buka konten', function (): void {
    // Angka ini yang menjadi dasar lencana "Terpopuler" di aplikasi, jadi ia
    // harus ada sejak nol — field yang hilang membuat lencananya lenyap tanpa
    // jejak, dan tidak ada yang tahu kenapa.
    $toko = tokoSiklus();
    $resep = Ebook::factory()->create(['berkas_path' => 'ebooks/contoh.pdf']);

    $this->actingAs($toko, 'pos');
    expect(jumlahBuka($resep->id))->toBe(0);

    $this->postJson(route('api.mobile.resep.klaim', $resep))->assertSuccessful();

    // Endpoint `unduh` juga yang dipakai aplikasi untuk membuka/pratinjau,
    // jadi membukanya sekali harus menaikkan angka yang sama. `fresh()`
    // penting: relasi `aksesPustaka` sudah termuat sebelum klaim, dan tanpa
    // memuat ulang pintunya masih terbaca tertutup.
    $this->actingAs($toko->fresh(), 'pos');
    $this->postJson(route('api.mobile.resep.unduh', $resep))->assertSuccessful();

    expect(jumlahBuka($resep->id))->toBe(1);
});
