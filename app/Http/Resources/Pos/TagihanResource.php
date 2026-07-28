<?php

declare(strict_types=1);

namespace App\Http\Resources\Pos;

use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Padanan `Tagihan` di aplikasi Flutter.
 *
 * `status` sudah memperhitungkan batas waktu: tagihan yang lewat batas tapi
 * masih tercatat "menunggu" adalah tagihan yang berbohong, dan layar
 * pembayaran akan terus menampilkan nomor VA yang sudah tidak bisa dibayar.
 *
 * @mixin Pembayaran
 */
class TagihanResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $saluran = $this->saluran;

        return [
            'id' => (string) $this->id,
            'nomorInvoice' => $this->nomor_invoice,
            'durasi' => $this->durasi->value,
            'durasiLabel' => $this->durasi->label(),
            'nominal' => $this->nominal,
            'saluran' => $saluran?->value,
            'saluranLabel' => $saluran?->label(),
            'pakaiKode' => $saluran?->pakaiKode() ?? false,
            'status' => $this->statusKini()->value,
            'dibuat' => $this->tanggal->toISOString(),
            'batasBayar' => $this->batas_bayar?->toISOString(),
            'berlakuSampai' => $this->berlaku_sampai?->toISOString(),
            'dibayarPada' => $this->dibayar_pada?->toISOString(),
            'kodeBayar' => $this->kode_bayar,
            'kodePerusahaan' => $this->kode_perusahaan,
            'qrUrl' => $this->qr_url,
            'tautanBayar' => $this->tautan_bayar,
        ];
    }
}
