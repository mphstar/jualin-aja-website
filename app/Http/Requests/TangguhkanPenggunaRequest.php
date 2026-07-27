<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TangguhkanPenggunaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Wajib, dan tidak boleh sekadar satu huruf: alasan ini yang nanti
            // dibaca orang lain di log aktivitas (PRD §F3.7).
            'alasan' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'alasan.required' => 'Alasan penangguhan wajib diisi.',
            'alasan.min' => 'Alasan minimal 5 karakter.',
        ];
    }
}
