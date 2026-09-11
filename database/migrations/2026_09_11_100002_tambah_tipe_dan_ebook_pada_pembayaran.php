<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table): void {
            $table->string('tipe')->default('LANGGANAN')->after('durasi');
            $table->foreignId('ebook_id')->nullable()->after('tipe')->constrained('ebook')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table): void {
            $table->dropForeign(['ebook_id']);
            $table->dropColumn(['tipe', 'ebook_id']);
        });
    }
};
