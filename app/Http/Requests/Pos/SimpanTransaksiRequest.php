<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use App\Enums\MetodeBayarPos;
use App\Enums\StatusTransaksi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanTransaksiRequest extends FormRequest
{
    use PunyaItemKeranjang;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...$this->aturanItem(),
            'metode' => ['required', Rule::enum(MetodeBayarPos::class)],
            // `BATAL` tidak diterima dari klien: transaksi tidak pernah dibuat
            // dalam keadaan batal, ia dibatalkan setelah ada.
            'status' => ['required', Rule::in([
                StatusTransaksi::Selesai->value,
                StatusTransaksi::Ditahan->value,
            ])],
            'pelanggan' => [
                Rule::requiredIf(fn (): bool => $this->string('status')->value() === StatusTransaksi::Ditahan->value),
                'nullable', 'string', 'min:2', 'max:120',
            ],
            'uangDiterima' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'pelanggan.required' => 'Nama pembeli wajib diisi untuk transaksi bayar nanti.',
        ];
    }

    public function metode(): MetodeBayarPos
    {
        return MetodeBayarPos::from((string) $this->string('metode'));
    }

    public function status(): StatusTransaksi
    {
        return StatusTransaksi::from((string) $this->string('status'));
    }

    public function pelanggan(): ?string
    {
        $nama = trim((string) $this->string('pelanggan'));

        return $nama === '' ? null : $nama;
    }

    public function uangDiterima(): ?int
    {
        return $this->has('uangDiterima') && $this->input('uangDiterima') !== null
            ? $this->integer('uangDiterima')
            : null;
    }
}
