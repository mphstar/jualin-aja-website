<?php

declare(strict_types=1);

/*
 * Halaman panel dirender Inertia tanpa data — gerbangnya cuma autentikasi.
 * Isi tiap halaman diambil sendiri oleh komponennya lewat /api/v1.
 */

it('mengalihkan tamu ke halaman login', function (string $jalur): void {
    $this->get($jalur)->assertRedirect('/login');
})->with([
    '/',
    '/dasbor',
    '/pengguna',
    '/langganan',
    '/resep',
    '/pembayaran',
    '/aktivitas',
    '/pengaturan',
]);

it('membuka halaman login untuk tamu', function (): void {
    $this->get('/login')->assertOk();
});

it('mengalihkan admin yang sudah masuk keluar dari halaman login', function (): void {
    admin();

    $this->get('/login')->assertRedirect();
});

it('mengarahkan beranda ke dasbor', function (): void {
    admin();

    $this->get('/')->assertRedirect('/dasbor');
});

it('merender halaman panel tanpa menitipkan data lewat props', function (string $jalur, string $komponen): void {
    admin();

    $this->get($jalur)
        ->assertOk()
        ->assertInertia(fn ($halaman) => $halaman
            ->component($komponen)
            // Hanya identitas admin yang dititipkan; sisanya lewat API.
            ->has('auth.admin')
        );
})->with([
    ['/dasbor', 'dasbor'],
    ['/pengguna', 'pengguna/indeks'],
    ['/langganan', 'langganan/indeks'],
    ['/resep', 'resep/indeks'],
    ['/resep/baru', 'resep/form'],
    ['/pembayaran', 'pembayaran/indeks'],
    ['/aktivitas', 'aktivitas/indeks'],
    ['/pengaturan', 'pengaturan/indeks'],
]);

it('meneruskan parameter rute sebagai prop halaman detail', function (string $jalur, string $komponen): void {
    admin();

    $this->get($jalur)
        ->assertOk()
        ->assertInertia(fn ($halaman) => $halaman->component($komponen)->where('id', '42'));
})->with([
    ['/pengguna/42', 'pengguna/detail'],
    ['/resep/42', 'resep/detail'],
    ['/resep/42/ubah', 'resep/form'],
    ['/pembayaran/42', 'pembayaran/detail'],
]);
