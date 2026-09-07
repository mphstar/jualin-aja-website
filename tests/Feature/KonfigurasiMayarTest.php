<?php

declare(strict_types=1);

use App\Models\Pengaturan;
use App\Support\KonfigurasiMayar;

it('memakai bawaan .env ketika belum ada nilai yang tersimpan', function (): void {
    config()->set('services.mayar.api_key', 'API-key-bawaan');
    config()->set('services.mayar.is_production', false);
    config()->set('services.mayar.timeout', 15);

    expect(KonfigurasiMayar::semua())->toEqual([
        'api_key' => 'API-key-bawaan',
        'is_production' => false,
        'timeout' => 15,
    ]);

    expect(KonfigurasiMayar::apiKey())->toBe('API-key-bawaan');
    expect(KonfigurasiMayar::produksi())->toBeFalse();
    expect(KonfigurasiMayar::timeout())->toBe(15);
});

it('menimpa bawaan .env dengan nilai yang tersimpan', function (): void {
    config()->set('services.mayar.api_key', 'API-key-bawaan');
    config()->set('services.mayar.is_production', false);
    config()->set('services.mayar.timeout', 15);

    // Hanya satu kunci yang tersimpan — sisanya harus tetap dari .env.
    Pengaturan::simpan(Pengaturan::KUNCI_MAYAR, [
        'api_key' => 'API-key-tersimpan',
        'is_production' => true,
    ]);

    expect(KonfigurasiMayar::apiKey())->toBe('API-key-tersimpan');
    expect(KonfigurasiMayar::produksi())->toBeTrue();
    expect(KonfigurasiMayar::timeout())->toBe(15);
});

it('menyimpan konfigurasi sebagai satu baris JSON di tabel pengaturan', function (): void {
    Pengaturan::simpan(Pengaturan::KUNCI_MAYAR, [
        'api_key' => 'API-key-abc123',
        'is_production' => false,
        'timeout' => 30,
    ]);

    $baris = Pengaturan::query()->find(Pengaturan::KUNCI_MAYAR);

    expect($baris)->not->toBeNull();
    expect($baris->kunci)->toBe(Pengaturan::KUNCI_MAYAR);
    expect($baris->nilai)->toEqual([
        'api_key' => 'API-key-abc123',
        'is_production' => false,
        'timeout' => 30,
    ]);
});

it('mengembalikan bawaan ketika kunci belum ada', function (): void {
    expect(Pengaturan::ambil('kunci-tidak-ada', 'bawaan'))->toBe('bawaan');
});
