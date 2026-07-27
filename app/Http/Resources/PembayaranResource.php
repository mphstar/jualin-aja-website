<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Padanan tipe `Pembayaran`.
 *
 * Kolom Midtrans sengaja TIDAK dikeluarkan: `snap_token` dan payload mentah
 * tidak dibutuhkan panel admin, dan mengirimnya berarti menyebar data gerbang
 * pembayaran ke tempat yang tidak memerlukannya.
 *
 * @mixin Pembayaran
 */
class PembayaranResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'nomorInvoice' => $this->nomor_invoice,
            'userId' => (string) $this->pos_user_id,
            'langgananId' => $this->langganan_id !== null ? (string) $this->langganan_id : null,
            'nominal' => $this->nominal,
            'durasi' => $this->durasi->value,
            'metode' => $this->metode->value,
            'status' => $this->status->value,
            'tanggal' => $this->tanggal->toISOString(),
            'catatan' => $this->catatan,
        ];
    }
}
