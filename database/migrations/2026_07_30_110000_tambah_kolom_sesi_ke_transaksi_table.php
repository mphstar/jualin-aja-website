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
        Schema::table('transaksi', function (Blueprint $table): void {
            $table->foreignId('sesi_kasir_id')
                ->nullable()
                ->after('pos_user_id')
                ->constrained('sesi_kasir')
                ->nullOnDelete();
            $table->string('nama_kasir')
                ->nullable()
                ->after('sesi_kasir_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table): void {
            $table->dropForeign(['sesi_kasir_id']);
            $table->dropColumn(['sesi_kasir_id', 'nama_kasir']);
        });
    }
};
