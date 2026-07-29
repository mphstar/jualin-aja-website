<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom yang dibutuhkan begitu tagihan benar-benar dibuat lewat Midtrans.
 *
 * `metode` yang sudah ada tetap jadi pengelompokan kasar untuk laporan admin
 * (Transfer Bank / QRIS / VA / E-Wallet / Manual). `saluran` menyimpan pilihan
 * persisnya — BCA atau Mandiri bukan hal yang sama bagi pembeli yang sedang
 * mencari menu di aplikasi banknya, tapi keduanya satu baris di laporan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table): void {
            $table->string('saluran')->nullable()->after('metode');

            /*
             * Midtrans menutup tagihan yang tidak dibayar. Batasnya disimpan,
             * bukan dihitung ulang dari `tanggal`, karena ia jawaban gerbang —
             * dan layar pembayaran menuliskannya sebagai janji ke pengguna.
             */
            $table->timestamp('batas_bayar')->nullable()->after('tanggal');

            /*
             * Tanggal berakhir langganan SETELAH tagihan ini lunas. Dihitung
             * saat tagihan dibuat supaya angka yang dijanjikan di layar
             * pembayaran sama persis dengan yang didapat setelah membayar,
             * meski pelunasannya baru masuk 20 jam kemudian.
             */
            $table->timestamp('berlaku_sampai')->nullable()->after('batas_bayar');

            /** Nomor Virtual Account atau bill key Mandiri. */
            $table->string('kode_bayar')->nullable()->after('berlaku_sampai');

            /** Biller code Mandiri (echannel). Null untuk saluran lain. */
            $table->string('kode_perusahaan')->nullable()->after('kode_bayar');

            /** URL gambar QR dari Midtrans — QRIS dan GoPay. */
            $table->text('qr_url')->nullable()->after('kode_perusahaan');

            /** Deeplink ke aplikasi e-wallet. */
            $table->text('tautan_bayar')->nullable()->after('qr_url');

            $table->index(['pos_user_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table): void {
            $table->dropIndex(['pos_user_id', 'tanggal']);
            $table->dropColumn([
                'saluran', 'batas_bayar', 'berlaku_sampai', 'kode_bayar',
                'kode_perusahaan', 'qr_url', 'tautan_bayar',
            ]);
        });
    }
};
