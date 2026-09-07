<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gerbang pembayaran langganan pindah dari Midtrans ke Mayar.
 *
 * Kolom `midtrans_*` hanyalah penyimpanan data gateway (id transaksi & payload
 * mentah), jadi diganti namanya saja — tidak ada nilai yang harus diubah.
 * `snap_token` dihapus karena khusus Midtrans dan tidak pernah dipakai.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table): void {
            $table->renameColumn('midtrans_order_id', 'mayar_order_id');
            $table->renameColumn('midtrans_transaction_id', 'mayar_transaction_id');
            $table->renameColumn('midtrans_payload', 'mayar_payload');
            $table->dropColumn('snap_token');
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table): void {
            $table->renameColumn('mayar_order_id', 'midtrans_order_id');
            $table->renameColumn('mayar_transaction_id', 'midtrans_transaction_id');
            $table->renameColumn('mayar_payload', 'midtrans_payload');
            $table->string('snap_token')->nullable()->after('mayar_transaction_id');
        });
    }
};
