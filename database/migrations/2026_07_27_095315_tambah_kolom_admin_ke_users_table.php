<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `users` menyimpan admin platform — bukan pemilik toko. Pemilik toko
 * ada di `pos_users` karena entitasnya berbeda: ia punya data usaha, tidak
 * pernah membuka panel ini, dan nanti login dari aplikasi mobile.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_url')->nullable()->after('email');
            $table->timestamp('terakhir_masuk')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['avatar_url', 'terakhir_masuk']);
        });
    }
};
