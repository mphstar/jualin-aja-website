<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\JenisKonten;
use App\Enums\KategoriEbook;
use App\Enums\KategoriPrompt;
use App\Enums\StatusEbook;

class DaftarEbookRequest extends DaftarRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...$this->aturanDaftar(),
            'jenis' => $this->aturanFilterEnum(JenisKonten::class),
            'kategori' => $this->aturanFilterEnum(KategoriEbook::class),
            'kategoriPrompt' => $this->aturanFilterEnum(KategoriPrompt::class),
            'status' => $this->aturanFilterEnum(StatusEbook::class),
        ];
    }

    public function jenis(): ?JenisKonten
    {
        return $this->filterEnum('jenis', JenisKonten::class);
    }

    public function kategori(): ?KategoriEbook
    {
        return $this->filterEnum('kategori', KategoriEbook::class);
    }

    public function kategoriPrompt(): ?KategoriPrompt
    {
        return $this->filterEnum('kategoriPrompt', KategoriPrompt::class);
    }

    public function status(): ?StatusEbook
    {
        return $this->filterEnum('status', StatusEbook::class);
    }
}
