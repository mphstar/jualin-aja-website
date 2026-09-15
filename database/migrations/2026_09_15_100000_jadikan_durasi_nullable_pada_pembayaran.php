<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tagihan Pustaka satuan tidak punya durasi.
 *
 * Ia pembelian barang, bukan perpanjangan masa aktif — tapi kolomnya NOT NULL,
 * jadi `BuatTagihanPustaka` terpaksa menulis `BULANAN` sebagai placeholder.
 * Baris itu lalu terbaca sebagai "pembelian langganan 1 bulan" oleh setiap
 * pembaca yang tidak memeriksa `tipe`, termasuk panel admin dan aplikasi.
 *
 * Nilai 'PUSTAKA_SATUAN' dan 'BULANAN' di bawah ditulis sebagai literal, sama
 * seperti migrasi `tambah_tipe_dan_ebook_pada_pembayaran`, supaya migrasi ini
 * tidak ikut rusak kalau nama konstanta di model berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table): void {
            $table->string('durasi')->nullable()->change();
        });

        DB::table('pembayaran')
            ->where('tipe', 'PUSTAKA_SATUAN')
            ->update(['durasi' => null]);
    }

    public function down(): void
    {
        // Kolomnya kembali NOT NULL, jadi baris lama harus diisi dulu.
        DB::table('pembayaran')
            ->whereNull('durasi')
            ->update(['durasi' => 'BULANAN']);

        Schema::table('pembayaran', function (Blueprint $table): void {
            $table->string('durasi')->nullable(false)->change();
        });
    }
};
