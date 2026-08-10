<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DurasiPaket;
use App\Enums\IkonKategori;
use App\Enums\JenisUsaha;
use App\Enums\SumberLangganan;
use App\Models\Kategori;
use App\Models\Langganan;
use App\Models\PengaturanStruk;
use App\Models\PosUser;
use App\Models\Produk;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AkunUjiSeeder extends Seeder
{
    public function run(): void
    {
        $sekarang = CarbonImmutable::now();

        // 1. Akun Gratis (Trial sudah habis 10 hari lalu)
        $gratis = PosUser::query()->updateOrCreate(
            ['email' => 'gratis@jualinaja.id'],
            [
                'nama' => 'User Gratis',
                'password' => 'password123',
                'telepon' => '081234567891',
                'nama_toko' => 'Toko Gratis POS',
                'jenis_usaha' => JenisUsaha::WarungMakan,
                'kota' => 'Jakarta',
                'alamat' => 'Jl. Sudirman No. 10',
                'tanggal_daftar' => $sekarang->subDays(24),
                'ditangguhkan' => false,
            ]
        );

        Langganan::query()->updateOrCreate(
            ['pos_user_id' => $gratis->id, 'durasi' => DurasiPaket::Trial],
            [
                'sumber' => SumberLangganan::Trial,
                'tanggal_mulai' => $sekarang->subDays(24),
                'tanggal_berakhir' => $sekarang->subDays(10),
                'catatan' => 'Uji coba telah berakhir',
            ]
        );
        $gratis->segarkanRingkasanLangganan();
        $this->isiProdukDefault($gratis);

        // 2. Akun Trial (Aktif Uji Coba 14 hari lagi)
        $trial = PosUser::query()->updateOrCreate(
            ['email' => 'trial@jualinaja.id'],
            [
                'nama' => 'User Trial',
                'password' => 'password123',
                'telepon' => '081234567892',
                'nama_toko' => 'Toko Trial POS',
                'jenis_usaha' => JenisUsaha::Kafe,
                'kota' => 'Bandung',
                'alamat' => 'Jl. Dago No. 45',
                'tanggal_daftar' => $sekarang,
                'ditangguhkan' => false,
            ]
        );

        Langganan::query()->updateOrCreate(
            ['pos_user_id' => $trial->id, 'durasi' => DurasiPaket::Trial],
            [
                'sumber' => SumberLangganan::Trial,
                'tanggal_mulai' => $sekarang,
                'tanggal_berakhir' => $sekarang->addDays(14),
                'catatan' => 'Uji coba aktif',
            ]
        );
        $trial->segarkanRingkasanLangganan();
        $this->isiProdukDefault($trial);

        // 3. Akun Langganan (Berlangganan Bulanan Aktif 30 hari lagi)
        $langganan = PosUser::query()->updateOrCreate(
            ['email' => 'langganan@jualinaja.id'],
            [
                'nama' => 'User Langganan',
                'password' => 'password123',
                'telepon' => '081234567893',
                'nama_toko' => 'Toko Langganan POS',
                'jenis_usaha' => JenisUsaha::Restoran,
                'kota' => 'Surabaya',
                'alamat' => 'Jl. Pemuda No. 88',
                'tanggal_daftar' => $sekarang->subMonths(1),
                'ditangguhkan' => false,
            ]
        );

        Langganan::query()->updateOrCreate(
            ['pos_user_id' => $langganan->id, 'durasi' => DurasiPaket::Bulanan],
            [
                'sumber' => SumberLangganan::Pembelian,
                'tanggal_mulai' => $sekarang,
                'tanggal_berakhir' => $sekarang->addDays(30),
                'catatan' => 'Langganan bulanan aktif',
            ]
        );
        $langganan->segarkanRingkasanLangganan();
        $this->isiProdukDefault($langganan);
    }

    private function isiProdukDefault(PosUser $toko): void
    {
        PengaturanStruk::query()->firstOrCreate(
            ['pos_user_id' => $toko->id],
            PengaturanStruk::bawaan(),
        );

        $kategori = Kategori::query()->firstOrCreate(
            ['pos_user_id' => $toko->id, 'nama' => 'Menu Utama'],
            ['ikon' => IkonKategori::LocalDrink]
        );

        if (Produk::query()->where('pos_user_id', $toko->id)->count() === 0) {
            Produk::query()->create([
                'pos_user_id' => $toko->id,
                'kategori_id' => $kategori->id,
                'nama' => 'Produk Contoh 1',
                'harga_jual' => 15_000,
                'stok' => 50,
                'lacak_stok' => true,
                'satuan' => 'pcs',
            ]);
            Produk::query()->create([
                'pos_user_id' => $toko->id,
                'kategori_id' => $kategori->id,
                'nama' => 'Produk Contoh 2',
                'harga_jual' => 25_000,
                'stok' => 30,
                'lacak_stok' => true,
                'satuan' => 'pcs',
            ]);
        }
    }
}
