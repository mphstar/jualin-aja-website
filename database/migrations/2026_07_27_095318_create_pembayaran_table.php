<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table): void {
            $table->id();
            $table->string('nomor_invoice')->unique();
            $table->foreignId('pos_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('langganan_id')->nullable()->constrained('langganan')->nullOnDelete();

            /*
             * Rupiah penuh, bukan sen — seluruh nominal di domain ini bilangan
             * bulat rupiah dan tidak pernah punya pecahan.
             */
            $table->unsignedBigInteger('nominal');
            $table->string('durasi');
            $table->string('metode');
            $table->string('status');
            $table->timestamp('tanggal');
            $table->text('catatan')->nullable();

            /*
             * Midtrans — belum dipakai. Kolom disiapkan sekarang supaya
             * penambahan gerbang pembayaran nanti tidak menuntut migrasi pada
             * tabel yang sudah berisi data produksi.
             */
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->string('midtrans_transaction_id')->nullable();
            $table->string('snap_token')->nullable();
            $table->json('midtrans_payload')->nullable();
            $table->timestamp('dibayar_pada')->nullable();

            $table->timestamps();

            $table->index(['status', 'tanggal']);
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
