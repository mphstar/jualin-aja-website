<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use App\Enums\MetodeBayarPos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LunasiTransaksiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'metode' => ['required', Rule::enum(MetodeBayarPos::class)],
            'uangDiterima' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
        ];
    }

    public function metode(): MetodeBayarPos
    {
        return MetodeBayarPos::from((string) $this->string('metode'));
    }

    public function uangDiterima(): ?int
    {
        return $this->has('uangDiterima') && $this->input('uangDiterima') !== null
            ? $this->integer('uangDiterima')
            : null;
    }
}
