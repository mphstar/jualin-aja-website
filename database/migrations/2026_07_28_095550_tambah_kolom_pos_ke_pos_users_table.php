<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom yang baru dibutuhkan begitu aplikasi POS benar-benar tersambung.
 *
 * `alamat` tercetak di kepala struk, jadi ia milik toko — bukan pengaturan
 * struk. `kota` yang sudah ada dipakai panel admin untuk pengelompokan, dan
 * keduanya sengaja tidak digabung: alamat berubah saat toko pindah, kota
 * dipakai sebagai dimensi laporan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_users', function (Blueprint $table): void {
            $table->string('alamat')->nullable()->after('kota');

            /*
             * Nomor struk berikutnya diturunkan dari struk terakhir, tapi
             * penghitungnya disimpan supaya nomor tidak pernah terpakai dua
             * kali walau transaksi lama dihapus. Lihat App\Actions\Pos\
             * SimpanTransaksiPos — kolom ini dikunci di dalam transaksi.
             */
            $table->unsignedBigInteger('nomor_struk_terakhir')->default(0)->after('alamat');

            $table->timestamp('terakhir_masuk')->nullable()->after('nomor_struk_terakhir');
        });
    }

    public function down(): void
    {
        Schema::table('pos_users', function (Blueprint $table): void {
            $table->dropColumn(['alamat', 'nomor_struk_terakhir', 'terakhir_masuk']);
        });
    }
};
