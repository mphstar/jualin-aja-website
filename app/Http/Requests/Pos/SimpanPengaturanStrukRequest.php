<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use App\Enums\LebarKertas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanPengaturanStrukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Panjangnya dibatasi lebar kertas yang paling sempit: 32 karakter
            // pada 58 mm. Teks yang lebih panjang akan terpotong di kertas,
            // bukan di layar — dan itu baru ketahuan setelah tercetak.
            'kepala' => ['present', 'nullable', 'string', 'max:60'],
            'kaki' => ['present', 'nullable', 'string', 'max:120'],
            'tampilkanAlamat' => ['required', 'boolean'],
            'tampilkanTelepon' => ['required', 'boolean'],
            'tampilkanNamaKasir' => ['required', 'boolean'],
            'lebar' => ['required', Rule::enum(LebarKertas::class)],
        ];
    }

    /** @return array<string, mixed> */
    public function nilai(): array
    {
        return [
            'kepala' => trim((string) $this->string('kepala')),
            'kaki' => trim((string) $this->string('kaki')),
            'tampilkan_alamat' => $this->boolean('tampilkanAlamat'),
            'tampilkan_telepon' => $this->boolean('tampilkanTelepon'),
            'tampilkan_nama_kasir' => $this->boolean('tampilkanNamaKasir'),
            'lebar' => LebarKertas::from((string) $this->string('lebar')),
        ];
    }
}
