<?php

declare(strict_types=1);

use App\Enums\JenisAksi;
use App\Models\LogAktivitas;
use App\Models\User;

/** Sanctum stateful hanya menyalakan sesi untuk permintaan dari domain frontend. */
function dariPanel(): Illuminate\Testing\TestResponse|Tests\TestCase
{
    return test()->withHeader('Origin', config('app.url'));
}

it('menerima kredensial yang benar dan membuka sesi', function (): void {
    $admin = User::factory()->create([
        'email' => 'admin@jualinaja.id',
        'password' => 'admin123',
    ]);

    $respons = dariPanel()->postJson('/api/v1/auth/login', [
        'email' => 'admin@jualinaja.id',
        'kataSandi' => 'admin123',
    ]);

    $respons->assertOk()->assertJsonPath('admin.email', 'admin@jualinaja.id');

    expect(auth()->id())->toBe($admin->id)
        ->and($admin->refresh()->terakhir_masuk)->not->toBeNull()
        ->and(LogAktivitas::query()->where('aksi', JenisAksi::Masuk->value)->count())->toBe(1);
});

it('menolak kata sandi salah lewat field kataSandi', function (): void {
    User::factory()->create(['email' => 'admin@jualinaja.id', 'password' => 'admin123']);

    dariPanel()->postJson('/api/v1/auth/login', [
        'email' => 'admin@jualinaja.id',
        'kataSandi' => 'salah',
    ])
        ->assertStatus(422)
        // Dilekatkan ke field supaya pesannya muncul di bawah input yang benar.
        ->assertJsonValidationErrors(['kataSandi']);

    expect(auth()->check())->toBeFalse();
});

it('membatasi percobaan masuk beruntun', function (): void {
    User::factory()->create(['email' => 'admin@jualinaja.id', 'password' => 'admin123']);

    foreach (range(1, 6) as $percobaan) {
        dariPanel()->postJson('/api/v1/auth/login', [
            'email' => 'admin@jualinaja.id',
            'kataSandi' => 'salah',
        ])->assertStatus(422);
    }

    dariPanel()->postJson('/api/v1/auth/login', [
        'email' => 'admin@jualinaja.id',
        'kataSandi' => 'admin123',
    ])->assertStatus(429);
});

it('mengembalikan profil admin yang sedang masuk', function (): void {
    $admin = admin(['name' => 'Bintang Pratama']);

    $this->getJson('/api/v1/auth/saya')
        ->assertOk()
        ->assertJson([
            'id' => (string) $admin->id,
            'nama' => 'Bintang Pratama',
        ]);
});

it('memperbarui profil admin dan mencatatnya', function (): void {
    admin(['name' => 'Nama Lama']);

    $this->patchJson('/api/v1/auth/profil', ['nama' => 'Nama Baru'])
        ->assertOk()
        ->assertJsonPath('nama', 'Nama Baru');

    expect(LogAktivitas::query()->where('aksi', JenisAksi::PengaturanUbah->value)->exists())->toBeTrue();
});

it('menolak email yang sudah dipakai admin lain', function (): void {
    User::factory()->create(['email' => 'lain@jualinaja.id']);
    admin();

    $this->patchJson('/api/v1/auth/profil', ['email' => 'lain@jualinaja.id'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('menutup seluruh endpoint data dari tamu', function (string $jalur): void {
    $this->getJson($jalur)->assertUnauthorized();
})->with([
    '/api/v1/auth/saya',
    '/api/v1/pengguna',
    '/api/v1/langganan',
    '/api/v1/ebook',
    '/api/v1/pembayaran',
    '/api/v1/aktivitas',
    '/api/v1/statistik/dasbor',
    '/api/v1/pengaturan/harga-paket',
]);
