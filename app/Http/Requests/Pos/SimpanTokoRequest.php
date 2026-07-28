<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use App\Enums\JenisUsaha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanTokoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'min:2', 'max:120'],
            'jenisUsaha' => ['required', Rule::enum(JenisUsaha::class)],
            'alamat' => ['required', 'string', 'min:5', 'max:255'],
            'telepon' => ['required', 'string', 'min:8', 'max:30'],
        ];
    }

    /** @return array<string, mixed> */
    public function nilai(): array
    {
        return [
            'nama_toko' => trim((string) $this->string('nama')),
            'jenis_usaha' => JenisUsaha::from((string) $this->string('jenisUsaha')),
            'alamat' => trim((string) $this->string('alamat')),
            'telepon' => trim((string) $this->string('telepon')),
        ];
    }
}
