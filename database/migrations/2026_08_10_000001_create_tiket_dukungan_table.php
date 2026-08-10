<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiket_dukungan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_user_id')
                ->constrained('pos_users')
                ->cascadeOnDelete();
            $table->string('nomor_tiket')->unique();
            $table->string('jenis'); // SARAN | KOMPLAIN | PERTANYAAN
            $table->string('subjek');
            $table->text('pesan');
            $table->string('status')->default('TERBUKA'); // TERBUKA | DIPROSES | SELESAI | DITUTUP
            $table->string('prioritas')->default('SEDANG'); // RENDAH | SEDANG | TINGGI
            $table->text('balasan_admin')->nullable();
            $table->timestamp('dibalas_pada')->nullable();
            $table->foreignId('dibalas_oleh_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiket_dukungan');
    }
};
