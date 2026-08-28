<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\StatusLangganan;
use App\Enums\SumberLangganan;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Query\Builder;

/**
 * Cerminan SQL dari KondisiLangganan::status().
 *
 * Ada dua sebab kelas ini terpisah dan bukan sekadar memfilter di PHP:
 * daftar pengguna harus bisa disaring & diurutkan di sisi server, dan angka
 * di tab /langganan harus dihitung dari kumpulan baris yang SAMA dengan isi
 * tabelnya (PRD catatan implementasi §4) — kalau tidak, angkanya tidak akan
 * pernah cocok.
 *
 * Perbandingan sengaja memakai RENTANG timestamp, bukan `whereDate()`:
 * hasilnya identik dengan selisih kalender di KondisiLangganan, tapi tetap
 * bisa memakai indeks. Uji di tests/Unit menjaga keduanya tidak menyimpang.
 */
final class FilterStatusLangganan
{
    /**
     * @template TBuilder of Builder
     *
     * @param  TBuilder  $query
     * @param  string  $kolomBerakhir  kolom timestamp berakhirnya langganan (boleh NULL)
     * @param  string  $kolomSumber  kolom SumberLangganan yang sepadan
     * @param  string  $kolomDitangguhkan  kolom boolean penangguhan pemilik toko
     * @return TBuilder
     */
    public static function terapkan(
        Builder $query,
        StatusLangganan $status,
        string $kolomBerakhir,
        string $kolomSumber,
        string $kolomDitangguhkan,
        ?CarbonInterface $sekarang = null,
    ): Builder {
        $hariIni = CarbonImmutable::instance($sekarang ?? CarbonImmutable::now())->startOfDay();
        $batasAkanBerakhir = $hariIni->addDays(KondisiLangganan::AMBANG_AKAN_BERAKHIR + 1);

        match ($status) {
            // Penangguhan menang atas semua kondisi tanggal.
            StatusLangganan::Nonaktif => $query->where($kolomDitangguhkan, true),

            StatusLangganan::Kedaluwarsa => $query
                ->where($kolomDitangguhkan, false)
                ->where(function (Builder $q) use ($kolomBerakhir, $hariIni): void {
                    $q->whereNull($kolomBerakhir)->orWhere($kolomBerakhir, '<', $hariIni);
                }),

            // sisa hari 0..7 — DAN bukan trial: trial selalu TRIAL selama valid.
            StatusLangganan::AkanBerakhir => $query
                ->where($kolomDitangguhkan, false)
                ->where($kolomBerakhir, '>=', $hariIni)
                ->where($kolomBerakhir, '<', $batasAkanBerakhir)
                ->where($kolomSumber, '!=', SumberLangganan::Trial->value),

            // Trial valid berapa pun sisa harinya (termasuk yang di bawah ambang
            // "akan berakhir" — uji coba baru hanya 3 hari).
            StatusLangganan::Trial => $query
                ->where($kolomDitangguhkan, false)
                ->where($kolomBerakhir, '>=', $hariIni)
                ->where($kolomSumber, SumberLangganan::Trial->value),

            StatusLangganan::Aktif => $query
                ->where($kolomDitangguhkan, false)
                ->where($kolomBerakhir, '>=', $batasAkanBerakhir)
                ->where($kolomSumber, '!=', SumberLangganan::Trial->value),
        };

        return $query;
    }
}
