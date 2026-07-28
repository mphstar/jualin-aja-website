<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\PosUser;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Siapa yang melakukan sebuah tindakan, sebagai teks yang ikut tersimpan.
 *
 * Ada karena `Auth::user()` bisa mengembalikan DUA jenis akun yang berbeda:
 * admin panel (`User`, punya `name`) dan pemilik toko (`PosUser`, punya `nama`
 * dan `nama_toko`). Middleware `auth:pos` menjadikan guard `pos` sebagai guard
 * bawaan begitu autentikasinya lolos, jadi Action yang sama bisa dipanggil
 * dari kedua sisi — dan membaca `->name` begitu saja menghasilkan null yang
 * baru terlihat sebagai galat NOT NULL di tengah transaksi pembayaran.
 */
final class NamaAktor
{
    public static function dari(?Authenticatable $aktor): string
    {
        return match (true) {
            $aktor instanceof User => $aktor->name,
            // Ditandai eksplisit sebagai aksi dari aplikasi, bukan "Sistem":
            // perpanjangan yang dibayar sendiri oleh pemilik toko adalah
            // peristiwa yang punya pelaku, dan pertanyaan "siapa yang
            // memperpanjang ini?" harus tetap punya jawaban.
            $aktor instanceof PosUser => $aktor->nama_toko.' (aplikasi POS)',
            default => 'Sistem',
        };
    }
}
