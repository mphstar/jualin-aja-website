<?php

declare(strict_types=1);

use App\Enums\DurasiPaket;
use App\Enums\StatusLangganan;
use App\Enums\SumberLangganan;
use App\Support\KondisiLangganan;
use Carbon\CarbonImmutable;

/*
 * Tabel kebenaran PRD §4.2. Kasus batas (0, 7, 8 hari) ditulis eksplisit
 * karena di situlah kesalahan "≤ vs <" bersembunyi tanpa terlihat.
 */

function berakhirDalam(int $hari): CarbonImmutable
{
    return CarbonImmutable::now()->startOfDay()->addDays($hari)->addHours(12);
}

it('mengembalikan NONAKTIF apa pun tanggalnya bila ditangguhkan', function (int $sisaHari): void {
    $status = KondisiLangganan::status(
        berakhirDalam($sisaHari),
        SumberLangganan::Pembelian,
        ditangguhkan: true,
    );

    expect($status)->toBe(StatusLangganan::Nonaktif);
})->with([-30, -1, 0, 7, 8, 400]);

it('mengembalikan KEDALUWARSA bila belum pernah berlangganan', function (): void {
    expect(KondisiLangganan::status(null, null, ditangguhkan: false))
        ->toBe(StatusLangganan::Kedaluwarsa);
});

it('menurunkan status dari sisa hari dan sumber', function (
    int $sisaHari,
    SumberLangganan $sumber,
    StatusLangganan $diharapkan,
): void {
    expect(KondisiLangganan::status(berakhirDalam($sisaHari), $sumber, ditangguhkan: false))
        ->toBe($diharapkan);
})->with([
    'lewat sehari → kedaluwarsa' => [-1, SumberLangganan::Pembelian, StatusLangganan::Kedaluwarsa],
    'lewat jauh → kedaluwarsa' => [-90, SumberLangganan::Trial, StatusLangganan::Kedaluwarsa],
    'berakhir hari ini → akan berakhir' => [0, SumberLangganan::Pembelian, StatusLangganan::AkanBerakhir],
    'tepat di ambang 7 hari → akan berakhir' => [7, SumberLangganan::Pembelian, StatusLangganan::AkanBerakhir],
    'trial tetap trial walau persis di ambang 7 hari' => [7, SumberLangganan::Trial, StatusLangganan::Trial],
    'sehari di luar ambang → aktif' => [8, SumberLangganan::Pembelian, StatusLangganan::Aktif],
    'sehari di luar ambang, sumber trial → trial' => [8, SumberLangganan::Trial, StatusLangganan::Trial],
    'perpanjangan manual → aktif' => [200, SumberLangganan::PerpanjanganManual, StatusLangganan::Aktif],
]);

it('menghitung sisa hari sebagai selisih kalender, bukan selisih jam', function (): void {
    $sekarang = CarbonImmutable::parse('2026-07-27 23:30');

    // Berakhir kurang dari sejam lagi, tapi hari kalendernya masih hari ini.
    expect(KondisiLangganan::sisaHari(CarbonImmutable::parse('2026-07-28 00:10'), $sekarang))->toBe(1)
        ->and(KondisiLangganan::sisaHari(CarbonImmutable::parse('2026-07-27 23:59'), $sekarang))->toBe(0)
        ->and(KondisiLangganan::sisaHari(CarbonImmutable::parse('2026-07-26 00:01'), $sekarang))->toBe(-1);
});

it('menyambung perpanjangan dari tanggal berakhir bila langganan masih berjalan', function (): void {
    $sekarang = CarbonImmutable::parse('2026-07-27 10:00');
    $berakhir = CarbonImmutable::parse('2026-08-20 10:00');

    $baru = KondisiLangganan::tanggalBerakhirBaru($berakhir, DurasiPaket::Bulanan, $sekarang);

    // Sisa hari yang sudah dibayar tidak boleh hangus.
    expect($baru->toDateString())->toBe('2026-09-20');
});

it('memulai perpanjangan dari hari ini bila langganan sudah kedaluwarsa', function (): void {
    $sekarang = CarbonImmutable::parse('2026-07-27 10:00');
    $berakhir = CarbonImmutable::parse('2026-05-01 10:00');

    $baru = KondisiLangganan::tanggalBerakhirBaru($berakhir, DurasiPaket::Semesteran, $sekarang);

    expect($baru->toDateString())->toBe('2027-01-27');
});

it('memulai perpanjangan dari hari ini bila belum pernah berlangganan', function (): void {
    $sekarang = CarbonImmutable::parse('2026-07-27 10:00');

    expect(KondisiLangganan::tanggalBerakhirBaru(null, DurasiPaket::Tahunan, $sekarang)->toDateString())
        ->toBe('2027-07-27');
});
