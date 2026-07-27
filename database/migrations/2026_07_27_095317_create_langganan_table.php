<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu baris = satu siklus langganan. Perpanjangan SELALU membuat baris baru,
 * riwayat lama tidak pernah ditimpa (PRD §F4.5), supaya jejak perpanjangan
 * tetap bisa ditelusuri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('langganan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_user_id')->constrained()->cascadeOnDelete();

            $table->string('durasi');
            $table->string('sumber');
            $table->timestamp('tanggal_mulai');
            $table->timestamp('tanggal_berakhir');

            $table->string('dibuat_oleh')->nullable();
            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index(['pos_user_id', 'tanggal_berakhir']);
            $table->index('tanggal_berakhir');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('langganan');
    }
};
