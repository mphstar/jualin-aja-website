<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Instrumen bayar native (native checkout).
 *
 * Alih-alih hanya menyimpan tautan halaman Mayar, sekarang backend menyimpan
 * instrumen yang sudah dinormalisasi (kode QR / VA / e-wallet) beserta
 * kedaluwarsa salurannya sendiri — yang bisa lebih awal dari kedaluwarsa
 * invoice dan menang atasnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table): void {
            $table->json('instruksi_bayar')->nullable()->after('tautan_bayar');

            /** Kedaluwarsa yang diberikan saluran (mis. jam hidup kode QR). */
            $table->timestamp('kedaluwarsa_saluran')->nullable()->after('instruksi_bayar');
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table): void {
            $table->dropColumn(['instruksi_bayar', 'kedaluwarsa_saluran']);
        });
    }
};
