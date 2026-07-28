<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use App\Actions\Pos\SimpanProdukPos;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @phpstan-import-type DataProduk from SimpanProdukPos
 */
class SimpanProdukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'min:2', 'max:120'],
            // Kepemilikan kategorinya diperiksa di Action, bukan di sini:
            // aturan `exists` yang menyertakan pos_user_id akan menggandakan
            // pengetahuan yang sudah dipegang SimpanProdukPos.
            'kategoriId' => ['required', 'integer', 'min:1'],
            'hargaJual' => ['required', 'integer', 'min:1', 'max:999999999'],
            'satuan' => ['nullable', 'string', 'max:20'],
            'lacakStok' => ['required', 'boolean'],
            // Nol diizinkan — produk yang stoknya memang habis harus tetap bisa
            // disimpan; yang tidak boleh cuma negatif.
            'stok' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'gambarUrl' => ['nullable', 'url', 'max:2048'],
        ];
    }

    /** @return DataProduk */
    public function nilai(): array
    {
        $satuan = trim((string) $this->string('satuan'));

        return [
            'nama' => trim((string) $this->string('nama')),
            'kategori_id' => $this->integer('kategoriId'),
            'harga_jual' => $this->integer('hargaJual'),
            'satuan' => $satuan === '' ? 'pcs' : $satuan,
            'lacak_stok' => $this->boolean('lacakStok'),
            'stok' => $this->integer('stok'),
            'gambar_url' => $this->has('gambarUrl') ? (string) $this->string('gambarUrl') : null,
        ];
    }
}
