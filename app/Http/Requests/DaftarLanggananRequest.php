<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DurasiPaket;
use App\Enums\StatusLangganan;

class DaftarLanggananRequest extends DaftarRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...$this->aturanDaftar(),
            'status' => $this->aturanFilterEnum(StatusLangganan::class),
            'durasi' => $this->aturanFilterEnum(DurasiPaket::class),
            'termasukRiwayat' => ['nullable', 'boolean'],
        ];
    }

    public function status(): ?StatusLangganan
    {
        return $this->filterEnum('status', StatusLangganan::class);
    }

    public function durasi(): ?DurasiPaket
    {
        return $this->filterEnum('durasi', DurasiPaket::class);
    }

    /**
     * Default false: satu baris per toko (siklus yang sedang berlaku), supaya
     * angka di tab status berarti "berapa toko", bukan "berapa baris riwayat".
     */
    public function termasukRiwayat(): bool
    {
        return $this->boolean('termasukRiwayat');
    }
}
