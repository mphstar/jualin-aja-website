<?php

declare(strict_types=1);

use App\Enums\JenisAksi;
use App\Models\LogAktivitas;
use App\Models\Pengaturan;

it('mengembalikan nilai bawaan dari konfigurasi ketika belum pernah disimpan', function (): void {
    admin();

    config()->set('services.midtrans.server_key', 'SB-Mid-server-bawaan');
    config()->set('services.midtrans.client_key', '');
    config()->set('services.midtrans.is_production', false);
    config()->set('services.midtrans.timeout', 15);

    $this->getJson('/api/v1/pengaturan/midtrans')
        ->assertOk()
        ->assertJson([
            'server_key' => 'SB-Mid-server-bawaan',
            'client_key' => '',
            'is_production' => false,
            'timeout' => 15,
        ]);
});

it('menyimpan konfigurasi midtrans dan mencetaknya ke log', function (): void {
    admin();

    $this->putJson('/api/v1/pengaturan/midtrans', [
        'server_key' => 'SB-Mid-server-abc123',
        'client_key' => 'SB-Mid-client-xyz789',
        'is_production' => false,
        'timeout' => 30,
    ])
        ->assertOk()
        ->assertJson([
            'server_key' => 'SB-Mid-server-abc123',
            'client_key' => 'SB-Mid-client-xyz789',
            'is_production' => false,
            'timeout' => 30,
        ]);

    expect(Pengaturan::query()->find(Pengaturan::KUNCI_MIDTRANS)?->nilai)
        ->toEqual([
            'server_key' => 'SB-Mid-server-abc123',
            'client_key' => 'SB-Mid-client-xyz789',
            'is_production' => false,
            'timeout' => 30,
        ])
        ->and(LogAktivitas::query()->where('aksi', JenisAksi::PengaturanUbah->value)->count())->toBe(1);
});

it('menimpa sebagian tanpa menghapus kredensial yang sudah tersimpan', function (): void {
    admin();

    Pengaturan::simpan(Pengaturan::KUNCI_MIDTRANS, [
        'server_key' => 'SB-Mid-server-abc123',
        'client_key' => 'SB-Mid-client-xyz789',
        'is_production' => false,
        'timeout' => 15,
    ]);

    // Hanya mengalihkan ke produksi — kredensial harus tetap utuh.
    $this->putJson('/api/v1/pengaturan/midtrans', ['is_production' => true])
        ->assertOk()
        ->assertJson([
            'server_key' => 'SB-Mid-server-abc123',
            'client_key' => 'SB-Mid-client-xyz789',
            'is_production' => true,
            'timeout' => 15,
        ]);
});

it('menolak batas waktu di luar rentang yang sah', function (): void {
    admin();

    $this->putJson('/api/v1/pengaturan/midtrans', [
        'server_key' => 'SB-Mid-server-abc123',
        'timeout' => 0,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['timeout']);
});

it('menolak mode yang bukan boolean', function (): void {
    admin();

    $this->putJson('/api/v1/pengaturan/midtrans', ['is_production' => 'katakanlah'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['is_production']);
});

it('menutup endpoint dari tamu', function (): void {
    $this->getJson('/api/v1/pengaturan/midtrans')->assertUnauthorized();
    $this->putJson('/api/v1/pengaturan/midtrans', [])->assertUnauthorized();
});
