<?php

declare(strict_types=1);

use App\Enums\DurasiPaket;
use App\Enums\JenisAksi;
use App\Enums\SumberLangganan;
use App\Enums\TargetAksi;
use App\Models\Langganan;
use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Models\PosUser;
use Carbon\CarbonImmutable;

beforeEach(fn () => admin());

it('menghitung kartu dasbor dari kondisi langganan yang sebenarnya', function (): void {
    // 2 aktif, 1 akan berakhir, 1 kedaluwarsa, 1 trial, 1 nonaktif.
    foreach ([[60, false], [120, false], [3, false], [-20, false]] as [$sisaHari, $ditangguhkan]) {
        $posUser = PosUser::factory()->create(['ditangguhkan' => $ditangguhkan]);
        Langganan::factory()->berakhirDalam($sisaHari)->create([
            'pos_user_id' => $posUser->id,
            'sumber' => SumberLangganan::Pembelian,
        ]);
    }
    $trial = PosUser::factory()->create();
    Langganan::factory()->berakhirDalam(12)->create([
        'pos_user_id' => $trial->id,
        'sumber' => SumberLangganan::Trial,
    ]);
    $nonaktif = PosUser::factory()->ditangguhkan()->create();
    Langganan::factory()->berakhirDalam(200)->create(['pos_user_id' => $nonaktif->id]);

    Pembayaran::factory()->create(['pos_user_id' => $trial->id, 'nominal' => 250_000, 'tanggal' => now()]);

    $dasbor = $this->getJson('/api/v1/statistik/dasbor')->assertOk()->json();

    expect($dasbor['totalUser'])->toBe(6)
        // Aktif + trial dihitung bersama: keduanya sedang berlangganan.
        ->and($dasbor['langgananAktif'])->toBe(3)
        ->and($dasbor['akanBerakhir'])->toBe(1)
        ->and($dasbor['kedaluwarsa'])->toBe(1)
        ->and($dasbor['pendapatanBulanIni'])->toBe(250_000);
});

it('mengembalikan deret 12 bulan untuk kedua grafik tren', function (string $jalur): void {
    PosUser::factory()->count(2)->create(['tanggal_daftar' => now()->subMonths(2)]);
    Pembayaran::factory()->create([
        'pos_user_id' => PosUser::factory()->create()->id,
        'nominal' => 99_000,
        'tanggal' => now()->subMonths(2),
    ]);

    $deret = $this->getJson($jalur)->assertOk()->json();

    expect($deret)->toHaveCount(12)
        ->and($deret[0])->toHaveKeys(['label', 'nilai'])
        // Bulan terakhir dalam deret adalah bulan berjalan.
        ->and($deret[11]['label'])->toBe(CarbonImmutable::now()->locale('id')->translatedFormat('M'))
        ->and(array_sum(array_column($deret, 'nilai')))->toBeGreaterThan(0);
})->with(['/api/v1/statistik/tren-pendaftaran', '/api/v1/statistik/tren-pendapatan']);

it('menyusun komposisi paket dengan urutan tetap', function (): void {
    foreach ([DurasiPaket::Bulanan, DurasiPaket::Bulanan, DurasiPaket::Tahunan] as $durasi) {
        $posUser = PosUser::factory()->create();
        Langganan::factory()->berakhirDalam(30)->create([
            'pos_user_id' => $posUser->id,
            'durasi' => $durasi,
        ]);
    }

    $komposisi = $this->getJson('/api/v1/statistik/komposisi-paket')->assertOk()->json();

    // Urutan tetap supaya potongan donat tidak berpindah warna antar muat.
    expect(array_column($komposisi, 'durasi'))->toBe(['TRIAL', 'BULANAN', 'SEMESTERAN', 'TAHUNAN'])
        ->and(array_column($komposisi, 'jumlah'))->toBe([0, 2, 0, 1]);
});

it('menyaring log aktivitas menurut aksi, rentang tanggal, dan pencarian', function (): void {
    LogAktivitas::factory()->create([
        'aksi' => JenisAksi::EbookHapus,
        'target_tipe' => TargetAksi::Ebook,
        'deskripsi' => 'Menghapus ebook "Sambal Andalan".',
        'waktu' => CarbonImmutable::parse('2026-05-02 10:00'),
    ]);
    LogAktivitas::factory()->create([
        'aksi' => JenisAksi::Masuk,
        'deskripsi' => 'Masuk ke panel admin.',
        'waktu' => CarbonImmutable::parse('2026-07-02 10:00'),
    ]);

    expect($this->getJson('/api/v1/aktivitas?aksi=EBOOK_HAPUS')->assertOk()->json('total'))->toBe(1)
        ->and($this->getJson('/api/v1/aktivitas?cari=Sambal')->assertOk()->json('total'))->toBe(1)
        ->and($this->getJson('/api/v1/aktivitas?dari=2026-06-01')->assertOk()->json('total'))->toBe(1)
        ->and($this->getJson('/api/v1/aktivitas')->assertOk()->json('total'))->toBe(2);
});

it('mengembalikan feed aktivitas terbaru lebih dulu', function (): void {
    LogAktivitas::factory()->create(['waktu' => now()->subDays(3), 'deskripsi' => 'Lama.']);
    LogAktivitas::factory()->create(['waktu' => now(), 'deskripsi' => 'Baru.']);

    $feed = $this->getJson('/api/v1/aktivitas/terbaru?batas=5')->assertOk()->json();

    expect($feed)->toHaveCount(2)->and($feed[0]['deskripsi'])->toBe('Baru.');
});

it('menyimpan harga paket dan mencatat perubahannya', function (): void {
    $this->getJson('/api/v1/pengaturan/harga-paket')
        ->assertOk()
        ->assertJson(['BULANAN' => 99_000, 'SEMESTERAN' => 499_000, 'TAHUNAN' => 899_000]);

    $this->putJson('/api/v1/pengaturan/harga-paket', [
        'BULANAN' => 129_000,
        'SEMESTERAN' => 499_000,
        'TAHUNAN' => 899_000,
    ])->assertOk()->assertJsonPath('BULANAN', 129_000);

    $log = LogAktivitas::query()->where('aksi', JenisAksi::PengaturanUbah->value)->sole();

    expect($log->deskripsi)->toContain('Rp 99.000 → Rp 129.000')
        // Durasi yang tidak berubah tidak ikut disebut.
        ->and($log->deskripsi)->not->toContain('6 Bulan')
        ->and($this->getJson('/api/v1/pengaturan/harga-paket')->json('BULANAN'))->toBe(129_000);
});

it('tidak mencatat apa pun bila harga disimpan tanpa perubahan', function (): void {
    $this->putJson('/api/v1/pengaturan/harga-paket', [
        'BULANAN' => 99_000,
        'SEMESTERAN' => 499_000,
        'TAHUNAN' => 899_000,
    ])->assertOk();

    expect(LogAktivitas::query()->count())->toBe(0);
});

it('menolak harga yang tidak masuk akal', function (array $harga): void {
    $this->putJson('/api/v1/pengaturan/harga-paket', $harga)->assertStatus(422);
})->with([
    'negatif' => [['BULANAN' => -1, 'SEMESTERAN' => 499_000, 'TAHUNAN' => 899_000]],
    'bukan angka' => [['BULANAN' => 'gratis', 'SEMESTERAN' => 499_000, 'TAHUNAN' => 899_000]],
    'tidak lengkap' => [['BULANAN' => 99_000]],
]);
