<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Halaman panel admin
|--------------------------------------------------------------------------
|
| Rute di sini HANYA memetakan URL ke komponen halaman — tidak ada satu pun
| yang mengirim data. Seluruh isi halaman diambil komponennya sendiri lewat
| `/api/v1/*` (lihat routes/api.php), supaya API yang sama bisa dipakai
| aplikasi POS mobile dan webhook pembayaran nanti tanpa bergantung Inertia.
|
| Parameter rute diteruskan sebagai prop karena Inertia tidak punya padanan
| `useParams()`: id-nya datang dari server, bukan dibaca ulang dari URL.
|
*/

Route::get('/login', fn () => Inertia::render('login'))
    ->middleware('guest')
    ->name('login');

Route::middleware('auth')->group(function (): void {
    Route::redirect('/', '/dasbor')->name('beranda');

    Route::inertia('/dasbor', 'dasbor')->name('dasbor');

    Route::inertia('/pengguna', 'pengguna/indeks')->name('pengguna');
    Route::get('/pengguna/{id}', fn (string $id) => Inertia::render('pengguna/detail', ['id' => $id]))
        ->name('pengguna.detail');

    Route::inertia('/langganan', 'langganan/indeks')->name('langganan');

    // `/resep/baru` WAJIB mendahului `/resep/{id}`, kalau tidak "baru"
    // tertangkap sebagai id ebook.
    Route::inertia('/resep', 'resep/indeks')->name('resep');
    Route::inertia('/resep/baru', 'resep/form')->name('resep.baru');
    Route::get('/resep/{id}', fn (string $id) => Inertia::render('resep/detail', ['id' => $id]))
        ->name('resep.detail');
    Route::get('/resep/{id}/ubah', fn (string $id) => Inertia::render('resep/form', ['id' => $id]))
        ->name('resep.ubah');

    Route::inertia('/pembayaran', 'pembayaran/indeks')->name('pembayaran');
    Route::get('/pembayaran/{id}', fn (string $id) => Inertia::render('pembayaran/detail', ['id' => $id]))
        ->name('pembayaran.detail');

    Route::inertia('/aktivitas', 'aktivitas/indeks')->name('aktivitas');

    Route::inertia('/tiket', 'tiket/indeks')->name('tiket');
    Route::get('/tiket/{id}', fn (string $id) => Inertia::render('tiket/detail', ['id' => $id]))
        ->name('tiket.detail');

    Route::inertia('/pengaturan', 'pengaturan/indeks')->name('pengaturan');
});
