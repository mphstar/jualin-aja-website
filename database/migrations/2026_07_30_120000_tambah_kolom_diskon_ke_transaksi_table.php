<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table): void {
            $table->unsignedBigInteger('subtotal')->default(0)->after('pelanggan');
            $table->string('diskon_tipe')->nullable()->after('subtotal');
            $table->unsignedBigInteger('diskon_nilai')->default(0)->after('diskon_tipe');
            $table->unsignedBigInteger('diskon_nominal')->default(0)->after('diskon_nilai');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table): void {
            $table->dropColumn(['subtotal', 'diskon_tipe', 'diskon_nilai', 'diskon_nominal']);
        });
    }
};
