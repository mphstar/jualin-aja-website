<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perkenalkan jenis konten pada pustaka (PRD §4.3).
 *
 * Sebelumnya seluruh isi `ebook` adalah resep. Kolom `jenis` membedakan konten
 * Resep dan Prompt. Data lama seluruhnya resep, jadi di-backfill ke `RESEP`
 * dan diberi default agar kolom tidak boleh kosong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ebook', function (Blueprint $table): void {
            $table->string('jenis', 20)->default('RESEP')->after('id');
            $table->string('kategori_prompt', 30)->nullable()->after('kategori');
            // Kategori resep kini opsional: konten Prompt tidak memakainya.
            $table->string('kategori')->nullable()->change();
        });

        DB::table('ebook')->whereNull('jenis')->update(['jenis' => 'RESEP']);
    }

    public function down(): void
    {
        Schema::table('ebook', function (Blueprint $table): void {
            $table->dropColumn(['jenis', 'kategori_prompt']);
        });
    }
};
