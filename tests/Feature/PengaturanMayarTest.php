<?php

declare(strict_types=1);

use App\Enums\JenisAksi;
use App\Models\LogAktivitas;
use App\Models\Pengaturan;

it('mengembalikan nilai bawaan dari konfigurasi ketika belum pernah disimpan', function (): void {
    admin();

    config()->set('services.mayar.api_key', 'API-key-bawaan');
    config()->set('services.mayar.is_production', false);
    config()->set('services.mayar.timeout', 15);

    $this->getJson('/api/v1/pengaturan/mayar')
        ->assertOk()
        ->assertJson([
            'api_key' => 'API-key-bawaan',
            'is_production' => false,
            'timeout' => 15,
        ]);
});

it('menyimpan konfigurasi Mayar dan mencetaknya ke log', function (): void {
    admin();

    $this->putJson('/api/v1/pengaturan/mayar', [
        'api_key' => 'API-key-abc123',
        'is_production' => false,
        'timeout' => 30,
    ])
        ->assertOk()
        ->assertJson([
            'api_key' => 'API-key-abc123',
            'is_production' => false,
            'timeout' => 30,
        ]);

    expect(Pengaturan::query()->find(Pengaturan::KUNCI_MAYAR)?->nilai)
        ->toEqual([
            'api_key' => 'API-key-abc123',
            'is_production' => false,
            'timeout' => 30,
        ])
        ->and(LogAktivitas::query()->where('aksi', JenisAksi::PengaturanUbah->value)->count())->toBe(1);
});

it('menimpa sebagian tanpa menghapus kredensial yang sudah tersimpan', function (): void {
    admin();

    Pengaturan::simpan(Pengaturan::KUNCI_MAYAR, [
        'api_key' => 'API-key-abc123',
        'is_production' => false,
        'timeout' => 15,
    ]);

    // Hanya mengalihkan ke produksi — kredensial harus tetap utuh.
    $this->putJson('/api/v1/pengaturan/mayar', ['is_production' => true])
        ->assertOk()
        ->assertJson([
            'api_key' => 'API-key-abc123',
            'is_production' => true,
            'timeout' => 15,
        ]);
});

it('menerima API key yang panjang', function (): void {
    admin();

    // API key Mayar bisa berbentuk token yang jauh melewati 255 karakter.
    $panjang = str_repeat('a', 512);

    $this->putJson('/api/v1/pengaturan/mayar', [
        'api_key' => $panjang,
    ])
        ->assertOk()
        ->assertJsonPath('api_key', $panjang);
});

it('menolak batas waktu di luar rentang yang sah', function (): void {
    admin();

    $this->putJson('/api/v1/pengaturan/mayar', [
        'api_key' => 'API-key-abc123',
        'timeout' => 0,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['timeout']);
});

it('menolak mode yang bukan boolean', function (): void {
    admin();

    $this->putJson('/api/v1/pengaturan/mayar', ['is_production' => 'katakanlah'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['is_production']);
});

it('menutup endpoint dari tamu', function (): void {
    $this->getJson('/api/v1/pengaturan/mayar')->assertUnauthorized();
    $this->putJson('/api/v1/pengaturan/mayar', [])->assertUnauthorized();
});
