<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Konfigurasi kredensial & mode Midtrans dari halaman Pengaturan.
 *
 * Dua hal yang dipakai bisnis bukan dua hal yang sama dengan yang dipakai
 * validasi: `server_key` yang dikirim (mungkin ditimpa nilai yang disamarkan)
 * dibaca lewat `konfigurasi()`, sedangkan aturan `sometimes` di atas membuat
 * perubahan sebagian tetap sah — menimpa mode saja tanpa mengetik ulang kunci.
 */
class SimpanPengaturanMidtransRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'server_key' => ['nullable', 'string', 'max:255'],
            'client_key' => ['nullable', 'string', 'max:255'],
            'is_production' => ['sometimes', 'required', 'boolean'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'string' => 'Kredensial harus berupa teks.',
            'max' => 'Kredensial terlalu panjang.',
            'boolean' => 'Nilai mode pembayaran tidak valid.',
            'integer' => 'Batas waktu harus berupa angka bulat.',
            'min' => 'Batas waktu minimal 1 detik.',
            'max' => 'Batas waktu maksimal 120 detik.',
        ];
    }

    /**
     * Hanya kunci yang benar-benar dikirim. Controller menggabungkannya dengan
     * nilai tersimpan, jadi menimpa mode saja tidak menghapus kredensial.
     *
     * @return array<string, mixed>
     */
    public function konfigurasi(): array
    {
        $data = [];

        if ($this->exists('server_key')) {
            $data['server_key'] = trim((string) $this->input('server_key'));
        }

        if ($this->exists('client_key')) {
            $data['client_key'] = trim((string) $this->input('client_key'));
        }

        if ($this->exists('is_production')) {
            $data['is_production'] = $this->boolean('is_production');
        }

        if ($this->exists('timeout')) {
            $data['timeout'] = $this->integer('timeout', 15);
        }

        return $data;
    }
}
