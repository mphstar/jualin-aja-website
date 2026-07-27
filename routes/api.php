<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AktivitasController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EbookController;
use App\Http\Controllers\Api\V1\LanggananController;
use App\Http\Controllers\Api\V1\PembayaranController;
use App\Http\Controllers\Api\V1\PengaturanController;
use App\Http\Controllers\Api\V1\PenggunaController;
use App\Http\Controllers\Api\V1\StatistikController;
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
    });
});
