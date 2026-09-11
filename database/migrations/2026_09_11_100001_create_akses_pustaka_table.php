<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akses_pustaka', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_user_id')->constrained('pos_users')->cascadeOnDelete();
            $table->foreignId('ebook_id')->constrained('ebook')->cascadeOnDelete();
            $table->string('jenis'); // 'RESEP' atau 'PROMPT'
            $table->string('tipe_akses'); // 'KLAIM_LANGGANAN' atau 'BELI_SATUAN'
            $table->foreignId('pembayaran_id')->nullable()->constrained('pembayaran')->nullOnDelete();
            $table->timestamps();

            $table->unique(['pos_user_id', 'ebook_id']);
            $table->index(['pos_user_id', 'jenis', 'tipe_akses']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akses_pustaka');
    }
};
