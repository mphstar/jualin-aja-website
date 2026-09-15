<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Padanan tipe `Pembayaran`.
 *
 * Kolom gateway sengaja TIDAK dikeluarkan: id dan payload mentah tidak
 * dibutuhkan panel admin, dan mengirimnya berarti menyebar data gerbang
 * pembayaran ke tempat yang tidak memerlukannya.
 *
 * `tipe` membedakan tagihan langganan dari pembelian satuan Pustaka. Panel
 * admin membutuhkannya karena baris Pustaka tidak punya `durasi` (null) dan
 * tidak punya `langgananId` — tanpa `tipe`, pembelian konten akan terbaca
 * sebagai perpanjangan langganan yang gagal.
 *
 * `ebookJudul` hanya diisi untuk baris Pustaka, dan hanya lewat relasi yang
 * sudah dimuat: model mematikan lazy-load di luar produksi.
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
            'tipe' => $this->tipe,
            'langgananId' => $this->langganan_id !== null ? (string) $this->langganan_id : null,
            'nominal' => $this->nominal,
            'durasi' => $this->durasi?->value,
            'ebookJudul' => $this->ebook_id === null ? null : $this->ebook?->judul,
            'metode' => $this->metode->value,
            'status' => $this->status->value,
            'tanggal' => $this->tanggal->toISOString(),
            'catatan' => $this->catatan,
        ];
    }
}
