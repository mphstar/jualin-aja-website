<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu penjualan di kasir.
 *
 * Totalnya TIDAK disimpan — ia diturunkan dari `transaksi_baris`. Total yang
 * disimpan terpisah dari barisnya adalah dua sumber kebenaran, dan salah
 * satunya pasti basi begitu isi piutang disunting sebelum dilunasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_user_id')->constrained()->cascadeOnDelete();

            /*
             * Nomornya berurut PER TOKO, bukan global. Unik gabungan, sehingga
             * dua toko boleh sama-sama punya STR/2026/0001 tanpa saling
             * menyerobot nomor — dan indeks yang sama dipakai untuk mencari
             * nomor terakhir saat menyusun nomor berikutnya.
             */
            $table->string('nomor_struk');
            $table->timestamp('waktu');

            $table->string('metode');
            $table->string('status');

            /** Siapa yang berutang. Hanya terisi untuk transaksi ditahan. */
            $table->string('pelanggan')->nullable();

            /** Uang tunai yang diserahkan pembeli. Null untuk non-tunai. */
            $table->unsignedBigInteger('uang_diterima')->nullable();

            $table->timestamps();

            $table->unique(['pos_user_id', 'nomor_struk']);
            $table->index(['pos_user_id', 'waktu']);
            $table->index(['pos_user_id', 'status', 'waktu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
