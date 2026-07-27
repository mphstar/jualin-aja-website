<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DurasiPaket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PerpanjangLanggananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'userId' => ['required', 'integer', 'exists:pos_users,id'],
            // Hanya durasi berbayar. Uji coba lahir otomatis saat pendaftaran
            // dan tidak pernah diperpanjang manual (PRD §4.1).
            'durasi' => ['required', Rule::in(array_map(
                static fn (DurasiPaket $d): string => $d->value,
                DurasiPaket::berbayar(),
            ))],
            'catatan' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'userId.exists' => 'Pengguna tidak ditemukan.',
            'durasi.in' => 'Uji coba tidak bisa diperpanjang manual.',
        ];
    }

    public function durasi(): DurasiPaket
    {
        return DurasiPaket::from((string) $this->string('durasi'));
    }
}
