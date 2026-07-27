<?php

declare(strict_types=1);

use App\Enums\DurasiPaket;
use App\Enums\JenisAksi;
use App\Enums\StatusLangganan;
use App\Enums\SumberLangganan;
use App\Models\Langganan;
use App\Models\LogAktivitas;
use App\Models\PosUser;
use Carbon\CarbonImmutable;

beforeEach(fn () => admin());

it('menampilkan satu baris per toko secara bawaan', function (): void {
    $posUser = PosUser::factory()->create();
    Langganan::factory()->berakhirDalam(-200)->create(['pos_user_id' => $posUser->id]);
    Langganan::factory()->berakhirDalam(60)->create(['pos_user_id' => $posUser->id]);

    $respons = $this->getJson('/api/v1/langganan')->assertOk();

    // Riwayat lama tidak ikut: satu toko = satu baris.
    expect($respons->json('total'))->toBe(1)
        ->and($respons->json('data.0.berlaku'))->toBeTrue()
        ->and($respons->json('data.0.sisaHari'))->toBe(60);
});

it('menyertakan siklus lama saat diminta', function (): void {
    $posUser = PosUser::factory()->create();
    Langganan::factory()->berakhirDalam(-200)->create(['pos_user_id' => $posUser->id]);
    Langganan::factory()->berakhirDalam(60)->create(['pos_user_id' => $posUser->id]);

    $respons = $this->getJson('/api/v1/langganan?termasukRiwayat=1')->assertOk();

    expect($respons->json('total'))->toBe(2)
        ->and(array_column($respons->json('data'), 'berlaku'))->toContain(false);
});

it('membuat angka tab persis menjumlah isi tabelnya', function (): void {
    // Kasus yang dulu bikin angka tab dan tabel berbeda (PRD catatan §4).
    foreach ([[-30, false], [3, false], [60, false], [400, false], [20, true]] as [$sisaHari, $ditangguhkan]) {
        $posUser = PosUser::factory()->create(['ditangguhkan' => $ditangguhkan]);
        Langganan::factory()->berakhirDalam($sisaHari)->create([
            'pos_user_id' => $posUser->id,
            'sumber' => SumberLangganan::Pembelian,
        ]);
    }

    $jumlah = $this->getJson('/api/v1/langganan/jumlah-per-status')->assertOk()->json();
    $totalTabel = $this->getJson('/api/v1/langganan')->assertOk()->json('total');

    expect($jumlah['SEMUA'])->toBe($totalTabel);

    // Tiap tab harus cocok dengan hasil menyaring tabel dengan status itu.
    foreach (StatusLangganan::cases() as $status) {
        $isiTabel = $this->getJson('/api/v1/langganan?status='.$status->value)->assertOk()->json('total');
        expect($jumlah[$status->value])->toBe($isiTabel);
    }

    expect(array_sum(array_diff_key($jumlah, ['SEMUA' => 0])))->toBe($jumlah['SEMUA']);
});

it('memperpanjang dari tanggal berakhir bila langganan masih berjalan', function (): void {
    $posUser = PosUser::factory()->create(['nama_toko' => 'Kopi Senja']);
    $lama = Langganan::factory()->berakhirDalam(30)->create(['pos_user_id' => $posUser->id]);

    $respons = $this->postJson('/api/v1/langganan/perpanjang', [
        'userId' => $posUser->id,
        'durasi' => DurasiPaket::Bulanan->value,
        'catatan' => 'Dibayar via transfer.',
    ])->assertCreated();

    $diharapkan = CarbonImmutable::parse($lama->tanggal_berakhir)->addMonth();

    expect(CarbonImmutable::parse($respons->json('tanggalBerakhir'))->toDateString())
        ->toBe($diharapkan->toDateString())
        // Riwayat lama tetap utuh (PRD §F4.5).
        ->and(Langganan::query()->where('pos_user_id', $posUser->id)->count())->toBe(2)
        ->and($lama->refresh()->tanggal_berakhir->toDateString())
        ->toBe(CarbonImmutable::parse($lama->tanggal_berakhir)->toDateString());
});

it('memperpanjang dari hari ini bila langganan sudah kedaluwarsa', function (): void {
    $posUser = PosUser::factory()->create();
    Langganan::factory()->berakhirDalam(-45)->create(['pos_user_id' => $posUser->id]);

    $respons = $this->postJson('/api/v1/langganan/perpanjang', [
        'userId' => $posUser->id,
        'durasi' => DurasiPaket::Semesteran->value,
    ])->assertCreated();

    expect(CarbonImmutable::parse($respons->json('tanggalBerakhir'))->toDateString())
        ->toBe(CarbonImmutable::now()->addMonths(6)->toDateString());
});

it('menyegarkan ringkasan langganan toko setelah perpanjangan', function (): void {
    $posUser = PosUser::factory()->create();
    Langganan::factory()->berakhirDalam(-10)->create(['pos_user_id' => $posUser->id]);

    expect($posUser->refresh()->status())->toBe(StatusLangganan::Kedaluwarsa);

    $this->postJson('/api/v1/langganan/perpanjang', [
        'userId' => $posUser->id,
        'durasi' => DurasiPaket::Tahunan->value,
    ])->assertCreated();

    // Observer yang menjaga kolom cache; tanpa itu status akan tetap basi.
    expect($posUser->refresh()->status())->toBe(StatusLangganan::Aktif)
        ->and($posUser->langganan_durasi)->toBe(DurasiPaket::Tahunan);
});

it('mencatat perpanjangan ke log aktivitas', function (): void {
    $posUser = PosUser::factory()->create(['nama_toko' => 'Warung Barokah']);

    $this->postJson('/api/v1/langganan/perpanjang', [
        'userId' => $posUser->id,
        'durasi' => DurasiPaket::Bulanan->value,
    ])->assertCreated();

    $log = LogAktivitas::query()->where('aksi', JenisAksi::LanggananPerpanjang->value)->sole();

    expect($log->target_label)->toBe('Warung Barokah')
        ->and($log->deskripsi)->toContain('Warung Barokah')
        ->and($log->deskripsi)->toContain('1 Bulan');
});

it('menolak perpanjangan uji coba', function (): void {
    $posUser = PosUser::factory()->create();

    $this->postJson('/api/v1/langganan/perpanjang', [
        'userId' => $posUser->id,
        'durasi' => DurasiPaket::Trial->value,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['durasi']);
});

it('menolak perpanjangan untuk pengguna yang tidak ada', function (): void {
    $this->postJson('/api/v1/langganan/perpanjang', [
        'userId' => 99999,
        'durasi' => DurasiPaket::Bulanan->value,
    ])->assertStatus(422)->assertJsonValidationErrors(['userId']);
});

it('mengembalikan riwayat langganan satu toko dari yang terbaru', function (): void {
    $posUser = PosUser::factory()->create();
    Langganan::factory()->count(3)->create(['pos_user_id' => $posUser->id]);

    $respons = $this->getJson("/api/v1/langganan/riwayat/{$posUser->id}")->assertOk();

    $mulai = array_column($respons->json(), 'tanggalMulai');
    $terurut = $mulai;
    rsort($terurut);

    expect($respons->json())->toHaveCount(3)->and($mulai)->toBe($terurut);
});
