<?php

declare(strict_types=1);

namespace App\Http\Resources\Pos;

use App\Models\PosUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Kondisi langganan toko, dari sudut pandang aplikasi POS.
 *
 * Status dan sisa hari DIHITUNG di server, tidak disimpan (PRD §4.2). Klien
 * boleh menghitungnya lagi untuk tampilan, tapi yang menentukan boleh-tidaknya
 * kasir dipakai adalah `bolehTransaksi` dari sini — jam perangkat yang digeser
 * mundur tidak boleh memperpanjang langganan siapa pun.
 *
 * @mixin PosUser
 */
class LanggananTokoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $berlaku = $this->langgananBerlaku;

        return [
            'durasi' => $this->langganan_durasi?->value,
            'durasiLabel' => $this->langganan_durasi?->label(),
            'sumber' => $this->langganan_sumber?->value,
            'tanggalMulai' => $berlaku?->tanggal_mulai?->toISOString(),
            'tanggalBerakhir' => $this->langganan_berakhir_pada?->toISOString(),
            'sisaHari' => $this->sisaHari(),
            'status' => $this->status()->value,
            'ditangguhkan' => $this->ditangguhkan,
            'alasanPenangguhan' => $this->alasan_penangguhan,
            'bolehTransaksi' => $this->langgananBerjalan(),
            // Pustaka hanya untuk paket Berbayar (Langganan). `langgananBerjalan()`
            // tidak cukup: akun Trial masih "berjalan" tapi belum membeli, dan
            // toh tidak boleh membuka Pustaka — jadi pakai `bolehAksesResep()`.
            'bolehUnduhResep' => $this->bolehAksesResep(),
        ];
    }
}
