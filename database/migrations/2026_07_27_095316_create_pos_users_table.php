<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pemilik toko — pengguna aplikasi POS mobile. Di panel admin ia hanya muncul
 * sebagai data (PRD §3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_users', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('telepon');
            $table->string('avatar_url')->nullable();

            $table->string('nama_toko');
            $table->string('jenis_usaha');
            $table->string('kota');
            $table->timestamp('tanggal_daftar');

            $table->boolean('ditangguhkan')->default(false);
            $table->text('alasan_penangguhan')->nullable();

            /*
             * Disiapkan untuk aplikasi POS mobile; belum dipakai panel admin,
             * jadi nullable. Sanctum sudah terpasang untuk token-nya.
             */
            $table->string('password')->nullable();

            /*
             * Ringkasan langganan yang sedang berlaku. Yang didenormalisasi
             * hanya TANGGAL — status tetap diturunkan darinya oleh
             * App\Support\KondisiLangganan, jadi tidak bisa basi (PRD §4.2).
             *
             * Alasannya: tabel /pengguna harus bisa memfilter dan mengurutkan
             * berdasarkan status & sisa hari di sisi server, dan tanpa kolom ini
             * setiap query butuh subkueri "langganan dengan tanggal_berakhir
             * terjauh". Disegarkan dari satu tempat saja: LanggananObserver.
             *
             * Tidak diberi foreign key: kolom ini cache, bukan relasi. Membuatnya
             * FK melingkar (pos_users → langganan → pos_users) hanya menyulitkan
             * penghapusan tanpa memberi jaminan tambahan.
             */
            $table->unsignedBigInteger('langganan_berlaku_id')->nullable()->index();
            $table->timestamp('langganan_berakhir_pada')->nullable();
            $table->string('langganan_durasi')->nullable();
            $table->string('langganan_sumber')->nullable();

            $table->timestamps();

            $table->index(['ditangguhkan', 'langganan_berakhir_pada']);
            $table->index('jenis_usaha');
            $table->index('tanggal_daftar');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_users');
    }
};
