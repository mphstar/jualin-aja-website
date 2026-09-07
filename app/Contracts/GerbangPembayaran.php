<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\SaluranBayar;
use App\Models\Pembayaran;
use App\Support\HasilCharge;

/**
 * Kontrak gerbang pembayaran langganan.
 *
 * Ada sebagai antarmuka, bukan kelas tunggal, karena dua alasan yang keduanya
 * praktis: uji fitur memasang gerbang tiruan tanpa menyentuh jaringan, dan
 * pindah gerbang nanti (Xendit, misalnya) tidak menyentuh satu pun Action.
 *
 * Implementasi bawaannya App\Services\MayarGerbang.
 */
interface GerbangPembayaran
{
    /**
     * Buat transaksi di gerbang untuk sebuah invoice.
     *
     * Yang dikembalikan adalah instruksi pembayaran — instrumen native yang
     * langsung digambar aplikasi mobile.
     */
    public function buatTransaksi(Pembayaran $pembayaran, SaluranBayar $saluran): HasilCharge;

    /**
     * Tanya status sebuah invoice ke gerbang.
     *
     * Dipakai tombol "Saya sudah bayar". Ia BUKAN jalur utama pelunasan —
     * yang utama adalah webhook — melainkan jaring pengaman untuk pengguna
     * yang sudah membayar tapi notifikasinya belum sampai.
     *
     * @return array<string, mixed> payload mentah dari gerbang
     */
    public function periksaStatus(Pembayaran $pembayaran): array;

    /**
     * Pastikan notifikasi berbentuk permintaan webhook Mayar.
     *
     * @param  array<string, mixed>  $payload
     */
    public function notifikasiSah(array $payload): bool;
}
