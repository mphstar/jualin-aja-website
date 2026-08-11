<?php

declare(strict_types=1);

use App\Http\Controllers\Api\MidtransController;
use App\Http\Controllers\Api\Mobile\AuthController as PosAuthController;
use App\Http\Controllers\Api\Mobile\BerandaController;
use App\Http\Controllers\Api\Mobile\EbookController as PosEbookController;
use App\Http\Controllers\Api\Mobile\KategoriController as PosKategoriController;
use App\Http\Controllers\Api\Mobile\LanggananController as PosLanggananController;
use App\Http\Controllers\Api\Mobile\LaporanController;
use App\Http\Controllers\Api\Mobile\ProdukController as PosProdukController;
use App\Http\Controllers\Api\Mobile\ProdukEksporImporController as PosProdukEksporImporController;
use App\Http\Controllers\Api\Mobile\SesiKasirController;
use App\Http\Controllers\Api\Mobile\TiketController as PosTiketController;
use App\Http\Controllers\Api\Mobile\TokoController as PosTokoController;
use App\Http\Controllers\Api\Mobile\TransaksiController as PosTransaksiController;
use App\Http\Controllers\Api\V1\AktivitasController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EbookController;
use App\Http\Controllers\Api\V1\LanggananController;
use App\Http\Controllers\Api\V1\PembayaranController;
use App\Http\Controllers\Api\V1\PengaturanController;
use App\Http\Controllers\Api\V1\PenggunaController;
use App\Http\Controllers\Api\V1\StatistikController;
use App\Http\Controllers\Api\V1\TiketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
|
| Seluruh data panel admin mengalir lewat sini — halaman Inertia hanya
| merender kerangkanya. Konsekuensinya API ini berdiri sendiri dan siap
| dipakai aplikasi POS mobile serta webhook Midtrans nanti tanpa dibongkar.
|
| Autentikasinya sesi (Sanctum stateful): cookie yang sama dengan halaman
| Inertia, bukan token di localStorage.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('auth/saya', [AuthController::class, 'saya'])->name('auth.saya');
        Route::patch('auth/profil', [AuthController::class, 'ubahProfil'])->name('auth.profil');

        /*
         * `akan-berakhir` HARUS mendahului `{pengguna}`, kalau tidak ia
         * tertangkap sebagai id pengguna. Aturan yang sama berlaku untuk
         * `ringkasan` dan `terbaru` di bawah.
         */
        Route::get('pengguna/akan-berakhir', [PenggunaController::class, 'akanBerakhir'])->name('pengguna.akan-berakhir');
        Route::get('pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
        Route::post('pengguna', [PenggunaController::class, 'store'])->name('pengguna.store');
        Route::get('pengguna/{pengguna}', [PenggunaController::class, 'show'])->name('pengguna.show');
        Route::post('pengguna/{pengguna}/tangguhkan', [PenggunaController::class, 'tangguhkan'])->name('pengguna.tangguhkan');
        Route::post('pengguna/{pengguna}/pulihkan', [PenggunaController::class, 'pulihkan'])->name('pengguna.pulihkan');

        Route::get('langganan/jumlah-per-status', [LanggananController::class, 'jumlahPerStatus'])->name('langganan.jumlah');
        Route::get('langganan/riwayat/{pengguna}', [LanggananController::class, 'riwayat'])->name('langganan.riwayat');
        Route::get('langganan', [LanggananController::class, 'index'])->name('langganan.index');
        Route::post('langganan/perpanjang', [LanggananController::class, 'perpanjang'])->name('langganan.perpanjang');

        Route::get('ebook', [EbookController::class, 'index'])->name('ebook.index');
        Route::post('ebook', [EbookController::class, 'store'])->name('ebook.store');
        Route::get('ebook/{ebook}', [EbookController::class, 'show'])->name('ebook.show');
        // POST, bukan PUT: badan multipart tidak terbaca PHP pada PUT, jadi
        // unggahan cover & PDF harus lewat POST.
        Route::post('ebook/{ebook}', [EbookController::class, 'update'])->name('ebook.update');
        Route::patch('ebook/{ebook}/status', [EbookController::class, 'ubahStatus'])->name('ebook.status');
        Route::delete('ebook/{ebook}', [EbookController::class, 'destroy'])->name('ebook.destroy');

        Route::get('pembayaran/ringkasan', [PembayaranController::class, 'ringkasan'])->name('pembayaran.ringkasan');
        Route::get('pembayaran', [PembayaranController::class, 'index'])->name('pembayaran.index');
        Route::get('pembayaran/{pembayaran}', [PembayaranController::class, 'show'])->name('pembayaran.show');
        Route::post('pembayaran/{pembayaran}/tandai-lunas', [PembayaranController::class, 'tandaiLunas'])->name('pembayaran.lunas');
        Route::post('pembayaran/{pembayaran}/tandai-gagal', [PembayaranController::class, 'tandaiGagal'])->name('pembayaran.gagal');

        Route::get('aktivitas/terbaru', [AktivitasController::class, 'terbaru'])->name('aktivitas.terbaru');
        Route::get('aktivitas', [AktivitasController::class, 'index'])->name('aktivitas.index');

        Route::get('statistik/dasbor', [StatistikController::class, 'dasbor'])->name('statistik.dasbor');
        Route::get('statistik/tren-pendaftaran', [StatistikController::class, 'trenPendaftaran'])->name('statistik.pendaftaran');
        Route::get('statistik/tren-pendapatan', [StatistikController::class, 'trenPendapatan'])->name('statistik.pendapatan');
        Route::get('statistik/komposisi-paket', [StatistikController::class, 'komposisiPaket'])->name('statistik.komposisi');

        Route::get('pengaturan/harga-paket', [PengaturanController::class, 'hargaPaket'])->name('pengaturan.harga');
        Route::put('pengaturan/harga-paket', [PengaturanController::class, 'simpanHargaPaket'])->name('pengaturan.harga.simpan');

        Route::get('tiket', [TiketController::class, 'index'])->name('tiket.index');
        Route::get('tiket/{tiket}', [TiketController::class, 'show'])->name('tiket.show');
        Route::post('tiket/{tiket}/respon', [TiketController::class, 'respon'])->name('tiket.respon');
        Route::patch('tiket/{tiket}/status', [TiketController::class, 'ubahStatus'])->name('tiket.status');
    });
});

/*
|--------------------------------------------------------------------------
| API aplikasi POS mobile
|--------------------------------------------------------------------------
|
| Terpisah dari `v1` di atas — bukan sekadar kerapian. Keduanya memakai guard
| yang berbeda dengan provider yang berbeda (`pos_users` vs `users`), jadi
| token yang salah tempat ditolak oleh Sanctum sendiri, bukan oleh pemeriksaan
| yang harus diingat di tiap controller.
|
| Autentikasinya token bearer: perangkatnya lain, jaringannya lain, dan ia
| harus tetap masuk berminggu-minggu tanpa membuka peramban.
|
*/

Route::prefix('mobile/v1')->name('api.mobile.')->group(function (): void {
    Route::post('auth/daftar', [PosAuthController::class, 'daftar'])
        ->middleware('throttle:5,1')
        ->name('auth.daftar');

    Route::post('auth/masuk', [PosAuthController::class, 'masuk'])
        ->middleware('throttle:10,1')
        ->name('auth.masuk');

    Route::middleware(['auth:pos', 'pos'])->group(function (): void {
        Route::post('auth/keluar', [PosAuthController::class, 'keluar'])->name('auth.keluar');
        Route::get('auth/saya', [PosAuthController::class, 'saya'])->name('auth.saya');
        Route::patch('auth/profil', [PosAuthController::class, 'ubahProfil'])->name('auth.profil');

        /*
         * Langganan & tagihan sengaja DI LUAR `langganan.berjalan`. Toko yang
         * langganannya habis justru paling butuh halaman ini — mengunci pintu
         * keluarnya sendiri membuat perpanjangan hanya bisa lewat dukungan.
         */
        Route::get('langganan', [PosLanggananController::class, 'show'])->name('langganan');
        Route::get('tagihan', [PosLanggananController::class, 'riwayatTagihan'])->name('tagihan.index');
        Route::post('tagihan', [PosLanggananController::class, 'buatTagihan'])
            ->middleware('throttle:12,1')
            ->name('tagihan.store');
        Route::get('tagihan/{pembayaran}', [PosLanggananController::class, 'tagihan'])->name('tagihan.show');
        Route::post('tagihan/{pembayaran}/periksa', [PosLanggananController::class, 'periksaTagihan'])
            ->middleware('throttle:20,1')
            ->name('tagihan.periksa');

        Route::get('resep', [PosEbookController::class, 'index'])->name('resep.index');
        Route::post('resep/{ebook}/unduh', [PosEbookController::class, 'unduh'])->name('resep.unduh');

        Route::get('tiket', [PosTiketController::class, 'index'])->name('tiket.index');
        Route::post('tiket', [PosTiketController::class, 'store'])->name('tiket.store');
        Route::get('tiket/{tiket}', [PosTiketController::class, 'show'])->name('tiket.show');

        /*
         * Rute operasional toko. Middleware-nya hanya menahan penulisan oleh
         * toko nonaktif — akun Gratis (kedaluwarsa) dan Trial tetap boleh
         * mencatat transaksi. Membaca selalu terbuka, karena kasir yang tidak
         * bisa melihat daftar produknya sendiri akan mengira datanya hilang.
         */
        Route::middleware('langganan.berjalan')->group(function (): void {
            Route::get('beranda', BerandaController::class)->name('beranda');
            Route::get('laporan', LaporanController::class)->name('laporan');

            Route::get('kategori', [PosKategoriController::class, 'index'])->name('kategori.index');
            Route::post('kategori', [PosKategoriController::class, 'store'])->name('kategori.store');
            // Sebelum `{kategori}`, kalau tidak "urutan" tertangkap sebagai id.
            Route::put('kategori/urutan', [PosKategoriController::class, 'urutkan'])->name('kategori.urutan');
            Route::patch('kategori/{kategori}', [PosKategoriController::class, 'update'])->name('kategori.update');
            Route::delete('kategori/{kategori}', [PosKategoriController::class, 'destroy'])->name('kategori.destroy');

            Route::get('produk/format-impor', [PosProdukEksporImporController::class, 'formatImpor'])->name('produk.format-impor');
            Route::get('produk/ekspor', [PosProdukEksporImporController::class, 'ekspor'])->name('produk.ekspor');
            Route::post('produk/impor', [PosProdukEksporImporController::class, 'impor'])->name('produk.impor');

            Route::get('produk', [PosProdukController::class, 'index'])->name('produk.index');
            Route::post('produk', [PosProdukController::class, 'store'])->name('produk.store');
            Route::patch('produk/{produk}', [PosProdukController::class, 'update'])->name('produk.update');
            Route::delete('produk/{produk}', [PosProdukController::class, 'destroy'])->name('produk.destroy');

            Route::get('transaksi/piutang', [PosTransaksiController::class, 'piutang'])->name('transaksi.piutang');
            Route::get('transaksi/nomor-berikutnya', [PosTransaksiController::class, 'nomorBerikutnya'])->name('transaksi.nomor');
            Route::get('transaksi', [PosTransaksiController::class, 'index'])->name('transaksi.index');
            Route::post('transaksi', [PosTransaksiController::class, 'store'])->name('transaksi.store');
            Route::get('transaksi/{transaksi}', [PosTransaksiController::class, 'show'])->name('transaksi.show');
            Route::post('transaksi/{transaksi}/lunasi', [PosTransaksiController::class, 'lunasi'])->name('transaksi.lunasi');
            Route::put('transaksi/{transaksi}/isi', [PosTransaksiController::class, 'ubahIsi'])->name('transaksi.isi');

            Route::get('toko', [PosTokoController::class, 'show'])->name('toko.show');
            Route::put('toko', [PosTokoController::class, 'update'])->name('toko.update');
            Route::get('toko/struk', [PosTokoController::class, 'struk'])->name('toko.struk');
            Route::put('toko/struk', [PosTokoController::class, 'simpanStruk'])->name('toko.struk.simpan');

            Route::get('sesi-kasir/aktif', [SesiKasirController::class, 'aktif'])->name('sesi-kasir.aktif');
            Route::post('sesi-kasir/buka', [SesiKasirController::class, 'buka'])->name('sesi-kasir.buka');
            Route::post('sesi-kasir/catat-transaksi', [SesiKasirController::class, 'catatTransaksi'])->name('sesi-kasir.catat-transaksi');
            Route::post('sesi-kasir/tutup', [SesiKasirController::class, 'tutup'])->name('sesi-kasir.tutup');
            Route::get('sesi-kasir/riwayat', [SesiKasirController::class, 'riwayat'])->name('sesi-kasir.riwayat');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Webhook gerbang pembayaran
|--------------------------------------------------------------------------
|
| Tanpa autentikasi — Midtrans menembaknya dari servernya sendiri. Yang
| membedakannya dari pengirim lain adalah `signature_key` di dalam badan
| permintaan, diverifikasi di dalam controller.
|
*/

Route::post('midtrans/notifikasi', [MidtransController::class, 'notifikasi'])
    ->name('api.midtrans.notifikasi');
