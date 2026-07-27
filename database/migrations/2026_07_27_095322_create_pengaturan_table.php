<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan bernilai bebas (saat ini hanya `harga_paket`). Dipakai kunci-nilai
 * karena isinya sedikit dan bentuknya berbeda-beda; kolom terpisah untuk tiap
 * pengaturan berarti migrasi baru setiap kali ada satu angka tambahan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan', function (Blueprint $table): void {
            $table->string('kunci')->primary();
            $table->json('nilai');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan');
    }
};
