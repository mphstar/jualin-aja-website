<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\JenisAksi;
use Carbon\CarbonImmutable;

class DaftarAktivitasRequest extends DaftarRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...$this->aturanDaftar(),
            'aksi' => $this->aturanFilterEnum(JenisAksi::class),
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ];
    }

    public function aksi(): ?JenisAksi
    {
        return $this->filterEnum('aksi', JenisAksi::class);
    }

    public function dari(): ?CarbonImmutable
    {
        $nilai = (string) $this->string('dari');

        return $nilai === '' ? null : CarbonImmutable::parse($nilai)->startOfDay();
    }

    public function sampai(): ?CarbonImmutable
    {
        $nilai = (string) $this->string('sampai');

        return $nilai === '' ? null : CarbonImmutable::parse($nilai)->endOfDay();
    }
}
