<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data contoh untuk pengembangan.
 *
 * Sebarannya dibuat mengikuti PRD §9.6 supaya dasbor, grafik, dan seluruh tab
 * status langsung berisi sesuatu yang layak ditinjau begitu `migrate --seed`
 * selesai — bukan halaman kosong yang harus diisi manual dulu.
 */
class DatabaseSeeder extends Seeder
{
    public const string NAMA_ADMIN = 'Bintang Pratama';

    public const string EMAIL_ADMIN = 'admin@gmail.com';

    public const string SANDI_ADMIN = '12345678';

    /** Harga contoh awal (PRD §4.1) — bisa diubah dari halaman Pengaturan. */
    private const array HARGA_PAKET = [
        'TRIAL' => 0,
        'BULANAN' => 99_000,
        'SEMESTERAN' => 499_000,
        'TAHUNAN' => 899_000,
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            // Produksi: database kosong + empat akun awal (idempotent, aman
            // dijalankan ulang tiap container naik). Lihat SeederProduksi.
            if (app()->isProduction()) {
                $this->call(SeederProduksi::class);

                return;
            }

            User::query()->create([
                'name' => self::NAMA_ADMIN,
                'email' => self::EMAIL_ADMIN,
                'password' => self::SANDI_ADMIN,
                'email_verified_at' => now(),
                'terakhir_masuk' => now(),
            ]);

            Pengaturan::simpan(Pengaturan::KUNCI_HARGA_PAKET, self::HARGA_PAKET);

            $this->call([
                PenggunaLanggananSeeder::class,
                AkunUjiSeeder::class,
                EbookSeeder::class,
                // Menempel pada toko yang sudah ada, jadi harus sesudahnya.
                PosSeeder::class,
                TiketSeeder::class,
                // Terakhir: log diturunkan dari data yang sudah ada, bukan dikarang.
                LogAktivitasSeeder::class,
            ]);
        });
    }
}
