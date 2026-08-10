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
            'diskonTipe' => ['nullable', Rule::in(['PERSEN', 'NOMINAL'])],
            'diskonNilai' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'pelanggan.required' => 'Nama pembeli wajib diisi untuk transaksi bayar nanti.',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator|\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Contracts\Validation\Validator $v): void {
            $user = $this->user();
            if ($user !== null && ! $user->bolehAksesVoucher()) {
                if (($this->diskonNilai() ?? 0) > 0 || $this->diskonTipe() !== null) {
                    $v->errors()->add('diskonNilai', 'Fitur diskon/voucher tidak tersedia untuk paket versi Gratis. Tingkatkan ke paket Trial atau Langganan.');
                }
            }
        });
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

    public function diskonTipe(): ?string
    {
        $tipe = strtoupper(trim((string) $this->string('diskonTipe')));

        return in_array($tipe, ['PERSEN', 'NOMINAL'], true) ? $tipe : null;
    }

    public function diskonNilai(): ?int
    {
        return $this->has('diskonNilai') && $this->input('diskonNilai') !== null
            ? $this->integer('diskonNilai')
            : null;
    }
}
