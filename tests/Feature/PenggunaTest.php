<?php

declare(strict_types=1);

use App\Enums\JenisAksi;
use App\Enums\JenisUsaha;
use App\Enums\SumberLangganan;
use App\Models\Ebook;
use App\Models\Langganan;
use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Models\PosUser;
use App\Models\UnduhanEbook;

beforeEach(fn () => admin());

it('mengembalikan daftar pengguna dalam amplop berhalaman', function (): void {
    PosUser::factory()->count(3)->create();

    $respons = $this->getJson('/api/v1/pengguna')->assertOk();

    expect($respons->json())->toBeHalaman()
        ->and($respons->json('total'))->toBe(3)
        ->and($respons->json('data.0'))->toHaveKeys([
            'id', 'nama', 'namaToko', 'jenisUsaha', 'status', 'sisaHari', 'durasi', 'langgananAktif',
        ]);
});

it('mencari pengguna lintas nama, toko, dan kota', function (string $kueri): void {
    PosUser::factory()->create([
        'nama' => 'Budi Santoso',
        'nama_toko' => 'Kopi Senja',
        'kota' => 'Bandung',
        'telepon' => '081234567890',
    ]);
    PosUser::factory()->create(['nama' => 'Dewi Lestari', 'nama_toko' => 'Bakery Mekar', 'kota' => 'Medan']);

    $respons = $this->getJson('/api/v1/pengguna?cari='.urlencode($kueri))->assertOk();

    expect($respons->json('total'))->toBe(1)
        ->and($respons->json('data.0.namaToko'))->toBe('Kopi Senja');
})->with(['Budi', 'Senja', 'Bandung', '08123']);

it('menyaring pengguna menurut jenis usaha', function (): void {
    PosUser::factory()->count(2)->create(['jenis_usaha' => JenisUsaha::Kafe]);
    PosUser::factory()->create(['jenis_usaha' => JenisUsaha::Bakery]);

    expect($this->getJson('/api/v1/pengguna?jenisUsaha=KAFE')->assertOk()->json('total'))->toBe(2)
        ->and($this->getJson('/api/v1/pengguna?jenisUsaha=SEMUA')->assertOk()->json('total'))->toBe(3);
});

it('memberi halaman sesuai permintaan', function (): void {
    PosUser::factory()->count(25)->create();

    $respons = $this->getJson('/api/v1/pengguna?perHalaman=10&halaman=3')->assertOk();

    expect($respons->json('total'))->toBe(25)
        ->and($respons->json('halaman'))->toBe(3)
        ->and($respons->json('perHalaman'))->toBe(10)
        ->and($respons->json('data'))->toHaveCount(5);
});

it('menolak parameter daftar yang tidak masuk akal', function (array $params): void {
    $this->getJson('/api/v1/pengguna?'.http_build_query($params))->assertStatus(422);
})->with([
    'status tak dikenal' => [['status' => 'ENTAH']],
    'perHalaman melebihi batas' => [['perHalaman' => 5000]],
    'arah urut tak dikenal' => [['urutArah' => 'menyamping']],
]);

it('menyusun detail pengguna beserta seluruh riwayatnya', function (): void {
    $posUser = PosUser::factory()->create();
    Langganan::factory()->count(2)->create(['pos_user_id' => $posUser->id]);
    Pembayaran::factory()->create(['pos_user_id' => $posUser->id]);
    UnduhanEbook::factory()->create([
        'pos_user_id' => $posUser->id,
        'ebook_id' => Ebook::factory()->create(['judul' => 'Bumbu Dasar'])->id,
    ]);

    $respons = $this->getJson("/api/v1/pengguna/{$posUser->id}")->assertOk();

    expect($respons->json('user.id'))->toBe((string) $posUser->id)
        ->and($respons->json('riwayatLangganan'))->toHaveCount(2)
        ->and($respons->json('riwayatPembayaran'))->toHaveCount(1)
        ->and($respons->json('riwayatUnduhan.0.judulEbook'))->toBe('Bumbu Dasar');
});

it('mengembalikan 404 untuk pengguna yang tidak ada', function (): void {
    $this->getJson('/api/v1/pengguna/99999')->assertNotFound();
});

it('menangguhkan pengguna dan mencatat alasannya', function (): void {
    $posUser = PosUser::factory()->create(['nama_toko' => 'Kopi Senja']);

    $this->postJson("/api/v1/pengguna/{$posUser->id}/tangguhkan", [
        'alasan' => 'Pembayaran tertunggak lebih dari 30 hari.',
    ])->assertOk()->assertJsonPath('ditangguhkan', true);

    expect($posUser->refresh()->alasan_penangguhan)->toBe('Pembayaran tertunggak lebih dari 30 hari.');

    $log = LogAktivitas::query()->where('aksi', JenisAksi::UserTangguhkan->value)->sole();
    expect($log->target_label)->toBe('Kopi Senja')
        ->and($log->deskripsi)->toContain('Pembayaran tertunggak');
});

it('mewajibkan alasan saat menangguhkan', function (): void {
    $posUser = PosUser::factory()->create();

    $this->postJson("/api/v1/pengguna/{$posUser->id}/tangguhkan", ['alasan' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['alasan']);
});

it('memulihkan pengguna dan menghapus alasannya', function (): void {
    $posUser = PosUser::factory()->ditangguhkan()->create();

    $this->postJson("/api/v1/pengguna/{$posUser->id}/pulihkan")
        ->assertOk()
        ->assertJsonPath('ditangguhkan', false);

    expect($posUser->refresh()->alasan_penangguhan)->toBeNull()
        ->and(LogAktivitas::query()->where('aksi', JenisAksi::UserPulihkan->value)->exists())->toBeTrue();
});

it('mengurutkan daftar akan-berakhir dari yang paling dekat habis', function (): void {
    foreach ([2, 6, 4] as $sisaHari) {
        $posUser = PosUser::factory()->create();
        Langganan::factory()->berakhirDalam($sisaHari)->create([
            'pos_user_id' => $posUser->id,
            'sumber' => SumberLangganan::Pembelian,
        ]);
    }
    // Yang masih lama tidak boleh ikut muncul.
    $aman = PosUser::factory()->create();
    Langganan::factory()->berakhirDalam(90)->create(['pos_user_id' => $aman->id]);

    $respons = $this->getJson('/api/v1/pengguna/akan-berakhir?batas=10')->assertOk();

    expect($respons->json())->toHaveCount(3)
        ->and(array_column($respons->json(), 'sisaHari'))->toBe([2, 4, 6]);
});
