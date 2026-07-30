<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sesi_kasir', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_user_id')
                ->constrained('pos_users')
                ->cascadeOnDelete();
            $table->string('kode_sesi')->unique();
            $table->string('nama_kasir');
            $table->timestamp('waktu_buka');
            $table->timestamp('waktu_tutup')->nullable();

            // Kas / Saldo Awal (Modal Shift)
            $table->unsignedInteger('kas_awal_tunai')->default(0);
            $table->unsignedInteger('kas_awal_qris')->default(0);
            $table->unsignedInteger('kas_awal_transfer')->default(0);

            // Omzet Penjualan Terkumpul
            $table->unsignedInteger('total_tunai')->default(0);
            $table->unsignedInteger('total_qris')->default(0);
            $table->unsignedInteger('total_transfer')->default(0);
            $table->unsignedInteger('jumlah_transaksi')->default(0);

            // Kas Fisik/Setoran Fisik yang Dihitung Kasir Saat Tutup Shift
            $table->unsignedInteger('kas_fisik_tunai')->nullable();
            $table->unsignedInteger('kas_fisik_qris')->nullable();
            $table->unsignedInteger('kas_fisik_transfer')->nullable();
            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesi_kasir');
    }
};
