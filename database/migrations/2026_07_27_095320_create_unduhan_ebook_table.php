<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unduhan_ebook', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ebook_id')->constrained('ebook')->cascadeOnDelete();
            $table->foreignId('pos_user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('tanggal');
            $table->timestamps();

            $table->index(['ebook_id', 'tanggal']);
            $table->index(['pos_user_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unduhan_ebook');
    }
};
