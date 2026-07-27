<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Pemformat untuk teks yang DISIMPAN di database — deskripsi log aktivitas.
 *
 * Sengaja terbatas pada dua fungsi ini. Sisanya (badge, kartu, tabel) tetap
 * diformat di frontend oleh lib/format.ts, karena bentuknya urusan tampilan.
 * Yang ada di sini ikut tersimpan permanen, jadi harus stabil.
 */
final class Format
{
    /** 1250000 → "Rp 1.250.000" */
    public static function rupiah(int $nilai): string
    {
        return 'Rp '.number_format($nilai, 0, ',', '.');
    }

    /** → "25 Jul 2026" */
    public static function tanggal(CarbonInterface $tanggal): string
    {
        return $tanggal->locale('id')->translatedFormat('j M Y');
    }

    /** → "Jul" — label sumbu grafik dasbor. */
    public static function bulanSingkat(CarbonInterface $tanggal): string
    {
        return $tanggal->locale('id')->translatedFormat('M');
    }
}
