<?php

declare(strict_types=1);

namespace App\Http\Resources\Pos;

use App\Enums\SaluranBayar;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Padanan `Tagihan` di aplikasi Flutter.
 *
 * `status` sudah memperhitungkan batas waktu: tagihan yang lewat batas tapi
 * masih tercatat "menunggu" adalah tagihan yang berbohong, dan layar
 * pembayaran akan terus menampilkan instrumen yang sudah tidak berlaku.
 *
 * `instruksi` adalah instrumen native yang dinormalisasi backend (kode QR,
 * nomor VA, atau aksi e-wallet). `tautanBayar` adalah halaman hosted Mayar —
 * cadangan yang dipakai ketika instrumen tidak dikenal.
 *
 * @mixin Pembayaran
 */
class TagihanResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $saluran = $this->saluran !== null
            ? SaluranBayar::tryFrom($this->saluran)
            : null;

        return [
            'id' => (string) $this->id,
            'nomorInvoice' => $this->nomor_invoice,
            'durasi' => $this->durasi->value,
            'durasiLabel' => $this->durasi->label(),
            'nominal' => $this->nominal,
            'saluran' => $saluran?->value,
            'saluranLabel' => $saluran?->label(),
            'status' => $this->statusKini()->value,
            'dibuat' => $this->tanggal->toISOString(),
            'batasBayar' => $this->batas_bayar?->toISOString(),
            'batasSaluran' => $this->kedaluwarsa_saluran?->toISOString(),
            'berlakuSampai' => $this->berlaku_sampai?->toISOString(),
            'dibayarPada' => $this->dibayar_pada?->toISOString(),
            'kodeBayar' => $this->kode_bayar,
            'kodePerusahaan' => $this->kode_perusahaan,
            'qrUrl' => $this->qr_url,
            'tautanBayar' => $this->tautan_bayar,
            'instruksi' => $this->instruksi_bayar,
        ];
    }
}
