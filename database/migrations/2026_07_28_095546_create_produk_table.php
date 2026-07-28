<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Barang yang dijual satu toko.
 *
 * `stok` sengaja UNSIGNED. Ia lapis terakhir, bukan satu-satunya: pengurangan
 * stok dijaga App\Actions\Pos\SimpanTransaksiPos lewat transaksi basis data +
 * UPDATE bersyarat. Tapi kolom bertanda akan menolak nilai negatif bahkan kalau
 * ada jalur lain yang lolos dari sana nanti — dan stok minus adalah angka yang
 * tidak berarti apa-apa bagi pemilik toko, sekaligus membuat laporan terlihat
 * rusak tanpa ada yang bisa memperbaikinya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_user_id')->constrained()->cascadeOnDelete();

            /*
             * restrictOnDelete, bukan cascade: menghapus kategori tidak boleh
             * ikut menghapus produknya. Aturan pemindahannya ada di
             * App\Actions\Pos\HapusKategoriPos.
             */
            $table->foreignId('kategori_id')->constrained('kategori')->restrictOnDelete();

            $table->string('nama');
            $table->unsignedBigInteger('harga_jual');
            $table->string('satuan')->default('pcs');

            $table->boolean('lacak_stok')->default(false);
            $table->unsignedInteger('stok')->default(0);

            $table->string('gambar_url')->nullable();

            $table->timestamps();

            $table->index(['pos_user_id', 'kategori_id']);
            $table->index(['pos_user_id', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};
