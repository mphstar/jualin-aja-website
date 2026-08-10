<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DurasiPaket;
use App\Enums\JenisUsaha;
use App\Enums\SumberLangganan;
use App\Models\Langganan;
use App\Models\Pengaturan;
use App\Models\PosUser;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Data awal PRODUKSI — database kosong + empat akun siap pakai.
 *
 * Tidak ada data contoh (tanpa 48 toko demo, ebook, tiket, transaksi, atau log
 * aktivitas). Yang dibuat hanya:
 *
 *   1. Admin panel      → admin@jualinaja.id / admin123
 *   2. Akun Langganan   → langganan@jualinaja.id / password123 (paket aktif)
 *   3. Akun Trial       → trial@jualinaja.id / password123 (uji coba aktif)
 *   4. Akun Gratis      → gratis@jualinaja.id / password123 (uji coba habis)
 *
 * Seeder ini sengaja IDEMPOTENT: `firstOrCreate`/pemeriksaan keberadaan,
 * sehingga aman dijalankan ulang setiap container naik tanpa menduplikasi
 * data atau menimpa kata sandi yang sudah diganti pemilik.
 */
class SeederProduksi extends Seeder
{
    public const string EMAIL_ADMIN = 'admin@jualinaja.id';

    public const string EMAIL_LANGGANAN = 'langganan@jualinaja.id';

    public const string EMAIL_TRIAL = 'trial@jualinaja.id';

    public const string EMAIL_GRATIS = 'gratis@jualinaja.id';

    /** Harga paket awal (PRD §4.1) — hanya diisi kalau belum pernah diset. */
    private const array HARGA_PAKET = [
        'TRIAL' => 0,
        'BULANAN' => 99_000,
        'SEMESTERAN' => 499_000,
        'TAHUNAN' => 899_000,
    ];

    public function run(): void
    {
        $sekarang = CarbonImmutable::now();

        // 1. Admin panel.
        User::query()->firstOrCreate(
            ['email' => self::EMAIL_ADMIN],
            [
                'name' => 'Admin JualinAja',
                'password' => 'admin123',
                'email_verified_at' => $sekarang,
                'terakhir_masuk' => $sekarang,
            ]
        );

        // Harga paket tidak boleh menimpa yang sudah diubah lewat halaman
        // Pengaturan pada container berikutnya.
        if (Pengaturan::ambil(Pengaturan::KUNCI_HARGA_PAKET) === null) {
            Pengaturan::simpan(Pengaturan::KUNCI_HARGA_PAKET, self::HARGA_PAKET);
        }

        // 2–4. Akun POS: langganan aktif, trial aktif, dan gratis (trial habis).
        $this->buatAkunLangganan($sekarang);
        $this->buatAkunTrial($sekarang);
        $this->buatAkunGratis($sekarang);
    }

    private function buatAkunLangganan(CarbonImmutable $sekarang): void
    {
        $toko = PosUser::query()->firstOrCreate(
            ['email' => self::EMAIL_LANGGANAN],
            [
                'nama' => 'Pemilik Langganan',
                'password' => 'password123',
                'telepon' => '081234567893',
                'nama_toko' => 'Toko Langganan',
                'jenis_usaha' => JenisUsaha::Restoran,
                'kota' => 'Jakarta',
                'tanggal_daftar' => $sekarang->subMonths(1),
            ]
        );

        if ($toko->langganan()->doesntExist()) {
            Langganan::query()->create([
                'pos_user_id' => $toko->id,
                'durasi' => DurasiPaket::Bulanan,
                'sumber' => SumberLangganan::Pembelian,
                'tanggal_mulai' => $sekarang,
                'tanggal_berakhir' => $sekarang->addDays(30),
                'catatan' => 'Langganan bulanan aktif.',
            ]);
            $toko->segarkanRingkasanLangganan();
        }
    }

    private function buatAkunTrial(CarbonImmutable $sekarang): void
    {
        $toko = PosUser::query()->firstOrCreate(
            ['email' => self::EMAIL_TRIAL],
            [
                'nama' => 'Pemilik Trial',
                'password' => 'password123',
                'telepon' => '081234567892',
                'nama_toko' => 'Toko Trial',
                'jenis_usaha' => JenisUsaha::Kafe,
                'kota' => 'Bandung',
                'tanggal_daftar' => $sekarang,
            ]
        );

        if ($toko->langganan()->doesntExist()) {
            Langganan::query()->create([
                'pos_user_id' => $toko->id,
                'durasi' => DurasiPaket::Trial,
                'sumber' => SumberLangganan::Trial,
                'tanggal_mulai' => $sekarang,
                'tanggal_berakhir' => $sekarang->addDays(14),
                'catatan' => 'Uji coba aktif.',
            ]);
            $toko->segarkanRingkasanLangganan();
        }
    }

    private function buatAkunGratis(CarbonImmutable $sekarang): void
    {
        $toko = PosUser::query()->firstOrCreate(
            ['email' => self::EMAIL_GRATIS],
            [
                'nama' => 'Pemilik Gratis',
                'password' => 'password123',
                'telepon' => '081234567891',
                'nama_toko' => 'Toko Gratis',
                'jenis_usaha' => JenisUsaha::WarungMakan,
                'kota' => 'Surabaya',
                'tanggal_daftar' => $sekarang->subDays(24),
            ]
        );

        if ($toko->langganan()->doesntExist()) {
            Langganan::query()->create([
                'pos_user_id' => $toko->id,
                'durasi' => DurasiPaket::Trial,
                'sumber' => SumberLangganan::Trial,
                'tanggal_mulai' => $sekarang->subDays(24),
                'tanggal_berakhir' => $sekarang->subDays(10),
                'catatan' => 'Uji coba telah berakhir.',
            ]);
            $toko->segarkanRingkasanLangganan();
        }
    }
}
