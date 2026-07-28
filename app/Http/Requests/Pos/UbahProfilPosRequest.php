<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use App\Models\PosUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UbahProfilPosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var PosUser $toko */
        $toko = $this->user();

        return [
            'nama' => ['required', 'string', 'min:2', 'max:120'],
            'email' => [
                'required', 'email', 'max:255',
                // Email ini yang dipakai untuk masuk; dua akun beremail sama
                // berarti satu di antaranya tidak akan pernah bisa masuk lagi.
                Rule::unique('pos_users', 'email')->ignore($toko->id),
            ],
            'telepon' => ['required', 'string', 'min:8', 'max:30'],
        ];
    }

    /** @return array<string, string> */
    public function nilai(): array
    {
        return [
            'nama' => trim((string) $this->string('nama')),
            'email' => mb_strtolower(trim((string) $this->string('email'))),
            'telepon' => trim((string) $this->string('telepon')),
        ];
    }
}
