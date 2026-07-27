<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Pembayaran;

/**
 * Kontrak gerbang pembayaran — BELUM ADA IMPLEMENTASINYA.
 *
 * Ditulis lebih dulu supaya bentuk data yang dibutuhkan Midtrans sudah
 * tercermin di skema `pembayaran` (midtrans_order_id, snap_token,
 * midtrans_payload) sejak sekarang, bukan lewat migrasi menyusul pada tabel
 * yang sudah berisi data.
 *
 * Saat Midtrans dipasang nanti:
 *   1. `composer require midtrans/midtrans-php`
 *   2. Buat App\Services\MidtransGerbang yang mengimplementasi antarmuka ini
 *      dan mengikat kredensial dari `config('services.midtrans')`.
 *   3. Endpoint webhook memverifikasi signature, lalu memanggil
 *      App\Actions\TandaiPembayaranLunas — satu-satunya jalur pelunasan,
 *      jadi perpanjangan langganan dan pencatatan log ikut otomatis.
 */
interface GerbangPembayaran
{
    /**
     * Terbitkan token Snap untuk sebuah invoice dan simpan jejaknya
     * (`snap_token`, `midtrans_order_id`) pada baris pembayaran.
     */
    public function terbitkanTokenPembayaran(Pembayaran $pembayaran): string;

    /**
     * Pastikan notifikasi berasal dari gerbang, bukan dari pengirim lain.
     *
     * @param  array<string, mixed>  $payload
     */
    public function notifikasiSah(array $payload): bool;
}
