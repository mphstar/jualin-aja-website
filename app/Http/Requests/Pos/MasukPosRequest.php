<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class MasukPosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'kataSandi' => ['required', 'string', 'max:255'],
            // Nama perangkat jadi nama token, supaya pemilik toko bisa
            // mengenali "Redmi 12 dapur" di daftar sesi nanti.
            'perangkat' => ['nullable', 'string', 'max:60'],
        ];
    }
}
