<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori produk milik satu toko.
 *
 * Urutannya disimpan, bukan diturunkan dari nama atau id: baris chip di layar
 * kasir mengikuti persis urutan ini, dan kasir menghafalnya lewat posisi jempol.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_user_id')->constrained()->cascadeOnDelete();

            $table->string('nama');

            /*
             * Nama ikon Material, bukan berkas gambar. Daftarnya tertutup di
             * sisi aplikasi (App\Enums\IkonKategori) supaya chip kasir tidak
             * pernah berisi ikon yang tidak berarti apa-apa untuk makanan.
             */
            $table->string('ikon')->default('category_outlined');
            $table->unsignedInteger('urutan')->default(0);

            $table->timestamps();

            $table->index(['pos_user_id', 'urutan']);
            $table->unique(['pos_user_id', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori');
    }
};
