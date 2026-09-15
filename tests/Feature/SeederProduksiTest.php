<?php

declare(strict_types=1);

use App\Models\Langganan;
use App\Models\Pengaturan;
use App\Models\PosUser;
use App\Models\User;
use Database\Seeders\SeederProduksi;
use Illuminate\Support\Facades\Hash;

/*
 * SeederProduksi adalah satu-satunya seeder yang berjalan di container
 * produksi — DatabaseSeeder memanggilnya begitu `app()->isProduction()`.
 *
 * Tidak ada data contoh di dalamnya, jadi yang perlu dibuktikan hanya tiga:
 * akun yang dibuat memang bisa dipakai masuk, tiap akun POS punya siklus
 * langganannya, dan menjalankannya berulang tidak menumpuk baris. Yang terakhir
 * itu bukan teori: entrypoint menjalankan `db:seed` SETIAP container naik.
 */

beforeEach(fn () => $this->seed(SeederProduksi::class));

it('membuat admin panel yang bisa dipakai masuk', function (): void {
    $admin = User::query()->sole();

    expect($admin->email)->toBe(SeederProduksi::EMAIL_ADMIN)
        ->and(Hash::check(SeederProduksi::SANDI_ADMIN, $admin->password))->toBeTrue();
});

it('membuat tiga akun POS beserta siklus langganannya', function (): void {
    expect(PosUser::query()->count())->toBe(3);

    foreach (PosUser::query()->get() as $toko) {
        expect($toko->langganan()->count())->toBe(1)
            // Kolom ringkasan harus ikut terisi. Tanpa ini panel membaca toko
            // yang berbayar sebagai kedaluwarsa, dan aplikasi POS menolak masuk.
            ->and($toko->langganan_berlaku_id)->not->toBeNull();
    }
});

it('tidak menumpuk baris saat dijalankan ulang', function (): void {
    $this->seed(SeederProduksi::class);
    $this->seed(SeederProduksi::class);

    expect(User::query()->count())->toBe(1)
        ->and(PosUser::query()->count())->toBe(3)
        ->and(Langganan::query()->count())->toBe(3);
});

it('tidak menimpa harga paket yang sudah diubah pemilik', function (): void {
    Pengaturan::simpan(Pengaturan::KUNCI_HARGA_PAKET, ['BULANAN' => 1]);

    $this->seed(SeederProduksi::class);

    expect(Pengaturan::ambil(Pengaturan::KUNCI_HARGA_PAKET))
        ->toBe(['BULANAN' => 1]);
});
