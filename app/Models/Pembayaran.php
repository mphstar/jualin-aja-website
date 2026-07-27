<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DurasiPaket;
use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use Database\Factories\PembayaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nomor_invoice
 * @property int $pos_user_id
 * @property int|null $langganan_id
 * @property int $nominal
 * @property DurasiPaket $durasi
 * @property MetodePembayaran $metode
 * @property StatusPembayaran $status
 * @property Carbon $tanggal
 * @property string|null $catatan
 * @property string|null $midtrans_order_id
 * @property string|null $midtrans_transaction_id
 * @property string|null $snap_token
 * @property array<string, mixed>|null $midtrans_payload
 * @property Carbon|null $dibayar_pada
 * @property-read PosUser $posUser
 */
#[Fillable([
    'nomor_invoice', 'pos_user_id', 'langganan_id', 'nominal', 'durasi',
    'metode', 'status', 'tanggal', 'catatan', 'midtrans_order_id',
    'midtrans_transaction_id', 'snap_token', 'midtrans_payload', 'dibayar_pada',
])]
class Pembayaran extends Model
{
    /** @use HasFactory<PembayaranFactory> */
    use HasFactory;

    protected $table = 'pembayaran';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
            'durasi' => DurasiPaket::class,
            'metode' => MetodePembayaran::class,
            'status' => StatusPembayaran::class,
            'tanggal' => 'datetime',
            'midtrans_payload' => 'array',
            'dibayar_pada' => 'datetime',
        ];
    }

    /** @return BelongsTo<PosUser, $this> */
    public function posUser(): BelongsTo
    {
        return $this->belongsTo(PosUser::class);
    }

    /** @return BelongsTo<Langganan, $this> */
    public function langganan(): BelongsTo
    {
        return $this->belongsTo(Langganan::class);
    }
}
