<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak audit (PRD §M7). Ditulis hanya lewat App\Services\PencatatAktivitas —
 * kalau boleh ditulis dari mana saja, satu jalur yang terlewat membuat seluruh
 * jejaknya bohong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_aktivitas', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('waktu');

            $table->foreignId('aktor_id')->nullable()->constrained('users')->nullOnDelete();
            /*
             * Nama aktor disalin, bukan dibaca lewat relasi: entri log harus
             * tetap terbaca apa adanya meski admin-nya kelak berganti nama
             * atau dihapus.
             */
            $table->string('aktor_nama');

            $table->string('aksi');
            $table->string('target_tipe');
            $table->string('target_id')->nullable();
            $table->string('target_label')->nullable();
            $table->text('deskripsi');

            $table->timestamps();

            $table->index(['aksi', 'waktu']);
            $table->index('waktu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
