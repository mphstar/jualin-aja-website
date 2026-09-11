<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Konfigurasi kredensial & mode Mayar dari halaman Pengaturan.
 *
 * Dua hal yang dipakai bisnis bukan dua hal yang sama dengan yang dipakai
 * validasi: `api_key` yang dikirim (mungkin ditimpa nilai yang disamarkan)
 * dibaca lewat `konfigurasi()`, sedangkan aturan `sometimes` di atas membuat
 * perubahan sebagian tetap sah — menimpa mode saja tanpa mengetik ulang kunci.
 */
class SimpanPengaturanMayarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'api_key' => ['nullable', 'string', 'max:2048'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'is_production' => ['sometimes', 'required', 'boolean'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'api_key.string' => 'Kredensial harus berupa teks.',
            'api_key.max' => 'Kredensial terlalu panjang.',
            'webhook_secret.string' => 'Webhook secret harus berupa teks.',
            'webhook_secret.max' => 'Webhook secret terlalu panjang.',
            'is_production.boolean' => 'Nilai mode pembayaran tidak valid.',
            'timeout.integer' => 'Batas waktu harus berupa angka bulat.',
            'timeout.min' => 'Batas waktu minimal 1 detik.',
            'timeout.max' => 'Batas waktu maksimal 120 detik.',
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

        if ($this->exists('api_key')) {
            $data['api_key'] = trim((string) $this->input('api_key'));
        }

        if ($this->exists('webhook_secret')) {
            $data['webhook_secret'] = trim((string) $this->input('webhook_secret'));
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
