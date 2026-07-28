<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use App\Enums\DurasiPaket;
use App\Enums\SaluranBayar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BuatTagihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Trial tidak ada di daftar: ia diberikan otomatis saat daftar,
            // bukan dijual.
            'durasi' => ['required', Rule::in(array_map(
                static fn (DurasiPaket $d): string => $d->value,
                DurasiPaket::berbayar(),
            ))],
            'saluran' => ['required', Rule::enum(SaluranBayar::class)],
        ];
    }

    public function durasi(): DurasiPaket
    {
        return DurasiPaket::from((string) $this->string('durasi'));
    }

    public function saluran(): SaluranBayar
    {
        return SaluranBayar::from((string) $this->string('saluran'));
    }
}
