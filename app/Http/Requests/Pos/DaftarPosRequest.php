<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use App\Enums\JenisUsaha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class DaftarPosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:pos_users,email'],
            'telepon' => ['required', 'string', 'max:20'],
            'namaToko' => ['required', 'string', 'max:255'],
            'jenisUsaha' => ['required', new Enum(JenisUsaha::class)],
            'kota' => ['required', 'string', 'max:255'],
            'kataSandi' => ['required', Password::min(8), 'confirmed'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.unique' => 'Email sudah terdaftar. Silakan masuk.',
            'kataSandi.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'namaToko.required' => 'Nama toko wajib diisi.',
        ];
    }
}
