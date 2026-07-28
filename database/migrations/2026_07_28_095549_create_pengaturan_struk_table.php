<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Apa yang tercetak di struk sebuah toko.
 *
 * Terpisah dari `pos_users` karena isinya pilihan PENYAJIAN, bukan fakta toko.
 * Nama dan alamat adalah identitas; "Terima kasih, sampai jumpa!" adalah gaya.
 * Menyatukannya berarti mengubah sapaan menuntut menyunting identitas toko.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_struk', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('kepala')->default('');
            $table->string('kaki')->default('');

            $table->boolean('tampilkan_alamat')->default(true);
            $table->boolean('tampilkan_telepon')->default(true);
            $table->boolean('tampilkan_nama_kasir')->default(false);

            /** MM58 atau MM80 — menentukan berapa karakter muat per baris. */
            $table->string('lebar')->default('MM58');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_struk');
    }
};
