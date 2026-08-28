<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\DurasiPaket;
use App\Enums\StatusLangganan;
use App\Enums\SumberLangganan;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Sumber kebenaran TUNGGAL untuk kondisi langganan (PRD §4.2, §F4.4).
 *
 * Status tidak pernah disimpan — selalu diturunkan di sini, supaya tidak bisa
 * basi. Setiap tempat yang menampilkan status (Resource, statistik, filter)
 * wajib lewat kelas ini; cerminan SQL-nya ada di FilterStatusLangganan dan
 * diuji agar tetap sepakat dengan fungsi di bawah.
 */
final class KondisiLangganan
{
    /** Ambang "akan berakhir" dalam hari. */
    public const int AMBANG_AKAN_BERAKHIR = 7;

    /** Lama masa uji coba saat pemilik toko baru mendaftar. */
    public const int LAMA_TRIAL_HARI = 14;

    /**
     * Sisa hari sampai langganan berakhir.
     *
     * Memakai selisih KALENDER, bukan jam: "berakhir hari ini" harus stabil
     * berapa pun jam halaman dibuka. Negatif berarti sudah lewat.
     */
    public static function sisaHari(CarbonInterface $tanggalBerakhir, ?CarbonInterface $sekarang = null): int
    {
        $sekarang ??= CarbonImmutable::now();

        return (int) $sekarang->startOfDay()->diffInDays($tanggalBerakhir->startOfDay(), false);
    }

    /**
     * Turunkan status dari tanggal berakhir + flag penangguhan.
     *
     * Urutan penilaian menentukan hasil dan tidak boleh ditukar:
     *   ditangguhkan → NONAKTIF (override, menang atas semua)
     *   tidak punya langganan / sudah lewat → KEDALUWARSA
     *   sumber trial (masih berlaku) → TRIAL  ← TANPA cek sisa hari
     *   sisa ≤ 7 hari → AKAN_BERAKHIR
     *   selain itu → AKTIF
     *
     * Sumber trial diperiksa SEBELUM ambang "akan berakhir" karena masa uji coba
     * baru hanya 3 hari — pasti jatuh di bawah ambang. Kalau dibalik, akun trial
     * langsung dianggap AKAN_BERAKHIR, lalu dipetakan `versiDariStatus` menjadi
     * Langganan dan boleh membuka Pustaka.
     */
    public static function status(
        ?CarbonInterface $tanggalBerakhir,
        ?SumberLangganan $sumber,
        bool $ditangguhkan,
        ?CarbonInterface $sekarang = null,
    ): StatusLangganan {
        if ($ditangguhkan) {
            return StatusLangganan::Nonaktif;
        }

        if ($tanggalBerakhir === null) {
            return StatusLangganan::Kedaluwarsa;
        }

        $sisa = self::sisaHari($tanggalBerakhir, $sekarang);

        if ($sisa < 0) {
            return StatusLangganan::Kedaluwarsa;
        }

        if ($sumber === SumberLangganan::Trial) {
            return StatusLangganan::Trial;
        }

        if ($sisa <= self::AMBANG_AKAN_BERAKHIR) {
            return StatusLangganan::AkanBerakhir;
        }

        return StatusLangganan::Aktif;
    }

    /**
     * Tanggal berakhir setelah perpanjangan (PRD §F4.4).
     *
     * Kalau langganan MASIH berlaku, durasi ditambahkan dari tanggal
     * berakhirnya — pemilik toko tidak kehilangan sisa hari yang sudah
     * dibayar. Kalau SUDAH kedaluwarsa, ditambahkan dari hari ini.
     */
    public static function tanggalBerakhirBaru(
        ?CarbonInterface $berakhirSaatIni,
        DurasiPaket $durasi,
        ?CarbonInterface $sekarang = null,
    ): CarbonImmutable {
        $sekarang = CarbonImmutable::instance($sekarang ?? CarbonImmutable::now());

        $titikMulai = $berakhirSaatIni !== null && $berakhirSaatIni->greaterThan($sekarang)
            ? CarbonImmutable::instance($berakhirSaatIni)
            : $sekarang;

        return $titikMulai->addMonths($durasi->bulan());
    }
}
