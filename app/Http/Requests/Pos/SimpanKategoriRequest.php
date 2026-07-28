<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use App\Enums\IkonKategori;
use App\Models\Kategori;
use App\Models\PosUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanKategoriRequest extends FormRequest
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

        // Route model binding mengembalikan object|string; disaring lewat
        // instanceof supaya aturan `ignore` tidak pernah menerima id karangan.
        $kategori = $this->route('kategori');

        return [
            'nama' => [
                'required', 'string', 'min:2', 'max:60',
                /*
                 * Unik per toko, bukan global. Dua kategori bernama sama di
                 * satu toko membuat chip kasir tidak bisa dibedakan — dan
                 * pemilik toko akan mengira salah satunya tidak tersimpan.
                 */
                Rule::unique('kategori', 'nama')
                    ->where('pos_user_id', $toko->id)
                    ->ignore($kategori instanceof Kategori ? $kategori->id : null),
            ],
            'ikon' => ['required', Rule::enum(IkonKategori::class)],
        ];
    }

    public function nama(): string
    {
        return trim((string) $this->string('nama'));
    }

    public function ikon(): IkonKategori
    {
        return IkonKategori::from((string) $this->string('ikon'));
    }
}
