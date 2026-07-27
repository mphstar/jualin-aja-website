<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Katalog ebook resep (PRD §M5). Modul konten — bukan input bahan & takaran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ebook', function (Blueprint $table): void {
            $table->id();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->string('kategori');
            $table->text('deskripsi');

            /*
             * Simpan PATH relatif pada disk `public`, bukan URL penuh. URL
             * dibentuk saat penyajian (Storage::url), supaya berpindah domain
             * atau ke S3 tidak menuntut penulisan ulang seluruh baris.
             */
            $table->string('cover_path')->nullable();
            $table->string('berkas_path')->nullable();
            $table->string('nama_berkas')->nullable();
            $table->unsignedBigInteger('ukuran_berkas_bytes')->nullable();
            $table->unsignedInteger('jumlah_halaman')->nullable();

            $table->string('status');
            $table->timestamp('tanggal_terbit')->nullable();

            /** Didenormalisasi supaya tabel katalog tidak perlu menghitung (PRD §6). */
            $table->unsignedInteger('jumlah_unduhan')->default(0);

            $table->timestamps();

            $table->index(['status', 'kategori']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ebook');
    }
};
