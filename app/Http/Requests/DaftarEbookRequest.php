<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\KategoriEbook;
use App\Enums\StatusEbook;

class DaftarEbookRequest extends DaftarRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...$this->aturanDaftar(),
            'kategori' => $this->aturanFilterEnum(KategoriEbook::class),
            'status' => $this->aturanFilterEnum(StatusEbook::class),
        ];
    }

    public function kategori(): ?KategoriEbook
    {
        return $this->filterEnum('kategori', KategoriEbook::class);
    }

    public function status(): ?StatusEbook
    {
        return $this->filterEnum('status', StatusEbook::class);
    }
}
