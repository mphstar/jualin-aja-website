<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DurasiPaket;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Harga paket per durasi (PRD §F1.5).
 *
 * Perubahan di sini TIDAK menyentuh invoice lama — nominal disimpan per
 * transaksi, jadi hanya perpanjangan berikutnya yang terpengaruh.
 */
class SimpanHargaPaketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'BULANAN' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'SEMESTERAN' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'TAHUNAN' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'TRIAL' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'Harga wajib diisi.',
            'integer' => 'Harga harus berupa angka bulat.',
            'min' => 'Harga tidak boleh negatif.',
        ];
    }

    /** @return array<string, int> */
    public function hargaPaket(): array
    {
        return [
            DurasiPaket::Trial->value => 0,
            DurasiPaket::Bulanan->value => $this->integer('BULANAN'),
            DurasiPaket::Semesteran->value => $this->integer('SEMESTERAN'),
            DurasiPaket::Tahunan->value => $this->integer('TAHUNAN'),
        ];
    }
}
