<?php

declare(strict_types=1);

use App\Models\Pengaturan;
use App\Support\KonfigurasiMidtrans;

it('memakai bawaan .env ketika belum ada nilai yang tersimpan', function (): void {
    config()->set('services.midtrans.server_key', 'SB-Mid-server-bawaan');
    config()->set('services.midtrans.client_key', 'SB-Mid-client-bawaan');
    config()->set('services.midtrans.is_production', false);
    config()->set('services.midtrans.timeout', 15);

    expect(KonfigurasiMidtrans::semua())->toEqual([
        'server_key' => 'SB-Mid-server-bawaan',
        'client_key' => 'SB-Mid-client-bawaan',
        'is_production' => false,
        'timeout' => 15,
    ]);

    expect(KonfigurasiMidtrans::serverKey())->toBe('SB-Mid-server-bawaan');
    expect(KonfigurasiMidtrans::clientKey())->toBe('SB-Mid-client-bawaan');
    expect(KonfigurasiMidtrans::produksi())->toBeFalse();
    expect(KonfigurasiMidtrans::timeout())->toBe(15);
});

it('menimpa bawaan .env dengan nilai yang tersimpan', function (): void {
    config()->set('services.midtrans.server_key', 'SB-Mid-server-bawaan');
    config()->set('services.midtrans.client_key', 'SB-Mid-client-bawaan');
    config()->set('services.midtrans.is_production', false);
    config()->set('services.midtrans.timeout', 15);

    // Hanya dua kunci yang tersimpan — sisanya harus tetap dari .env.
    Pengaturan::simpan(Pengaturan::KUNCI_MIDTRANS, [
        'server_key' => 'SB-Mid-server-tersimpan',
        'is_production' => true,
    ]);

    expect(KonfigurasiMidtrans::serverKey())->toBe('SB-Mid-server-tersimpan');
    expect(KonfigurasiMidtrans::produksi())->toBeTrue();
    expect(KonfigurasiMidtrans::clientKey())->toBe('SB-Mid-client-bawaan');
    expect(KonfigurasiMidtrans::timeout())->toBe(15);
});

it('menyimpan konfigurasi sebagai satu baris JSON di tabel pengaturan', function (): void {
    Pengaturan::simpan(Pengaturan::KUNCI_MIDTRANS, [
        'server_key' => 'SB-Mid-server-abc123',
        'client_key' => 'SB-Mid-client-xyz789',
        'is_production' => false,
        'timeout' => 30,
    ]);

    $baris = Pengaturan::query()->find(Pengaturan::KUNCI_MIDTRANS);

    expect($baris)->not->toBeNull();
    expect($baris->kunci)->toBe(Pengaturan::KUNCI_MIDTRANS);
    expect($baris->nilai)->toEqual([
        'server_key' => 'SB-Mid-server-abc123',
        'client_key' => 'SB-Mid-client-xyz789',
        'is_production' => false,
        'timeout' => 30,
    ]);
});

it('mengembalikan bawaan ketika kunci belum ada', function (): void {
    expect(Pengaturan::ambil('kunci-tidak-ada', 'bawaan'))->toBe('bawaan');
});
