<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu baris di dalam struk.
 *
 * Nama dan harga adalah SALINAN, bukan rujukan ke produk. Kalau pemilik toko
 * menaikkan harga besok, struk kemarin harus tetap menunjukkan harga kemarin —
 * struk yang berubah sendiri bukan struk. `produk_id` tetap disimpan hanya
 * sebagai jejak untuk mengembalikan stok saat isi piutang disunting; ia
 * nullable karena produknya boleh dihapus tanpa merusak struk lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_baris', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi')->cascadeOnDelete();
            $table->foreignId('produk_id')->nullable()->constrained('produk')->nullOnDelete();

            $table->string('nama');
            $table->unsignedBigInteger('harga_satuan');
            $table->unsignedInteger('jumlah');

            $table->timestamps();

            $table->index('transaksi_id');
            $table->index('produk_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_baris');
    }
};
