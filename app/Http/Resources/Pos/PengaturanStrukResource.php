<?php

declare(strict_types=1);

namespace App\Http\Resources\Pos;

use App\Models\PengaturanStruk;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PengaturanStruk
 */
class PengaturanStrukResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'kepala' => $this->kepala,
            'kaki' => $this->kaki,
            'tampilkanAlamat' => $this->tampilkan_alamat,
            'tampilkanTelepon' => $this->tampilkan_telepon,
            'tampilkanNamaKasir' => $this->tampilkan_nama_kasir,
            'lebar' => $this->lebar->value,
            'karakterPerBaris' => $this->lebar->karakterPerBaris(),
        ];
    }
}
