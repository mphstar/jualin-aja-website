<?php

declare(strict_types=1);

use App\Enums\StatusLangganan;
use App\Enums\SumberLangganan;
use App\Models\Langganan;
use App\Models\PosUser;
use App\Support\FilterStatusLangganan;

/*
 * FilterStatusLangganan adalah cerminan SQL dari KondisiLangganan::status().
 * Dua cerminan gampang menyimpang tanpa ada yang sadar — uji ini menahannya
 * dengan membandingkan hasil kedua jalur atas dataset yang sama persis.
 */

function siapkanKasusStatus(): void
{
    $kasus = [
        [-90, SumberLangganan::Pembelian, false],
        [-1, SumberLangganan::Pembelian, false],
        [0, SumberLangganan::Pembelian, false],
        [3, SumberLangganan::Trial, false],
        [7, SumberLangganan::Pembelian, false],
        [8, SumberLangganan::Trial, false],
        [8, SumberLangganan::Pembelian, false],
        [45, SumberLangganan::PerpanjanganManual, false],
        [400, SumberLangganan::Trial, false],
        // Ditangguhkan pada berbagai posisi tanggal — semuanya harus NONAKTIF.
        [-5, SumberLangganan::Pembelian, true],
        [20, SumberLangganan::Pembelian, true],
        [3, SumberLangganan::Trial, true],
    ];

    foreach ($kasus as [$sisaHari, $sumber, $ditangguhkan]) {
        $posUser = PosUser::factory()->create(['ditangguhkan' => $ditangguhkan]);
        Langganan::factory()
            ->berakhirDalam($sisaHari)
            ->create(['pos_user_id' => $posUser->id, 'sumber' => $sumber]);
    }

    // Satu toko tanpa langganan sama sekali → harus terhitung kedaluwarsa.
    PosUser::factory()->create(['ditangguhkan' => false]);
}

it('menyaring pos_users persis sama dengan perhitungan status di PHP', function (StatusLangganan $status): void {
    siapkanKasusStatus();

    $query = PosUser::query();
    FilterStatusLangganan::terapkan(
        $query, $status,
        'langganan_berakhir_pada', 'langganan_sumber', 'ditangguhkan',
    );
    $lewatSql = $query->pluck('id')->sort()->values()->all();

    $lewatPhp = PosUser::query()->get()
        ->filter(fn (PosUser $u): bool => $u->status() === $status)
        ->pluck('id')->sort()->values()->all();

    expect($lewatSql)->toBe($lewatPhp)
        ->and($lewatSql)->not->toBeEmpty();
})->with(StatusLangganan::cases());

it('membagi seluruh baris tepat sekali ke lima status', function (): void {
    siapkanKasusStatus();
    $total = PosUser::query()->count();

    $terhitung = 0;
    foreach (StatusLangganan::cases() as $status) {
        $query = PosUser::query();
        FilterStatusLangganan::terapkan(
            $query, $status,
            'langganan_berakhir_pada', 'langganan_sumber', 'ditangguhkan',
        );
        $terhitung += $query->count();
    }

    // Tidak ada baris yang jatuh di dua status atau tidak masuk mana pun.
    expect($terhitung)->toBe($total);
});
