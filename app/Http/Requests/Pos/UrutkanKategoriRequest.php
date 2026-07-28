<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class UrutkanKategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'urutan' => ['required', 'array', 'min:1'],
            'urutan.*' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return list<int> */
    public function urutan(): array
    {
        return array_map('intval', array_values((array) $this->input('urutan', [])));
    }
}
