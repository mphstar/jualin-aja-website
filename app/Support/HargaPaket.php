<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\DurasiPaket;
use App\Models\Pengaturan;

/**
 * Harga langganan — satu tempat, sengaja.
 *
 * Dibaca tiga pemakai yang berbeda: halaman Pengaturan admin, dialog
 * perpanjangan manual, dan pembuatan tagihan dari aplikasi POS. Kalau angkanya
 * tersebar, perubahan harga akan selalu menyisakan satu tempat yang terlewat —
 * dan yang terlewat itu biasanya baru ketahuan lewat invoice bernominal lama.
 *
 * Nominal invoice yang SUDAH terbit tidak ikut berubah: ia tersimpan per baris
 * pembayaran, bukan dibaca ulang dari sini.
 */
final class HargaPaket
{
    /** Harga contoh awal (PRD §4.1) — hanya dipakai bila belum pernah diubah. */
    public const array BAWAAN = [
        'TRIAL' => 0,
        'BULANAN' => 99_000,
        'SEMESTERAN' => 499_000,
        'TAHUNAN' => 899_000,
    ];

    /** @return array<string, int> */
    public static function semua(): array
    {
        /** @var array<string, int> $tersimpan */
        $tersimpan = Pengaturan::ambil(Pengaturan::KUNCI_HARGA_PAKET, self::BAWAAN);

        // Digabung dengan bawaan supaya durasi yang belum pernah disetel tidak
        // menghilang dari daftar harga — hilangnya akan terbaca sebagai gratis.
        return [...self::BAWAAN, ...$tersimpan];
    }

    public static function untuk(DurasiPaket $durasi): int
    {
        return self::semua()[$durasi->value] ?? 0;
    }
}
