<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DurasiPaket;
use App\Enums\JenisUsaha;
use App\Enums\StatusLangganan;

class DaftarPenggunaRequest extends DaftarRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...$this->aturanDaftar(),
            'status' => $this->aturanFilterEnum(StatusLangganan::class),
            'durasi' => $this->aturanFilterEnum(DurasiPaket::class),
            'jenisUsaha' => $this->aturanFilterEnum(JenisUsaha::class),
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

    public function jenisUsaha(): ?JenisUsaha
    {
        return $this->filterEnum('jenisUsaha', JenisUsaha::class);
    }
}
