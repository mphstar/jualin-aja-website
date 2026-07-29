<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DurasiPaket;
use App\Enums\JenisUsaha;
use App\Enums\SumberLangganan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class SimpanPosUserRequest extends FormRequest
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
            'alamat' => ['nullable', 'string', 'max:500'],
            'password' => ['required', Password::min(8), 'confirmed'],

            // Langganan opsional — admin bisa mengatur bebas.
            'langganan' => ['nullable', 'array'],
            'langganan.durasi' => ['required_with:langganan', Rule::in(array_map(
                static fn (DurasiPaket $d): string => $d->value,
                DurasiPaket::cases(),
            ))],
            'langganan.sumber' => ['required_with:langganan', new Enum(SumberLangganan::class)],
            'langganan.lamaHari' => ['required_with:langganan', 'integer', 'min:1', 'max:3650'],
            'langganan.catatan' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.unique' => 'Email sudah dipakai pengguna lain.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'namaToko.required' => 'Nama toko wajib diisi.',
        ];
    }
}
