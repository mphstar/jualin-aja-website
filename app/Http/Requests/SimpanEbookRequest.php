<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\KategoriEbook;
use App\Enums\StatusEbook;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * Dikirim sebagai multipart/form-data karena membawa cover dan berkas PDF.
 */
class SimpanEbookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'min:3', 'max:180'],
            'kategori' => ['required', Rule::enum(KategoriEbook::class)],
            'deskripsi' => ['required', 'string', 'min:10', 'max:2000'],
            'status' => ['required', Rule::enum(StatusEbook::class)],
            'jumlahHalaman' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'cover' => ['nullable', 'image', 'max:4096'],
            'berkas' => ['nullable', 'file', 'mimes:pdf', 'max:51200'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'judul.required' => 'Judul wajib diisi.',
            'judul.min' => 'Judul minimal 3 karakter.',
            'deskripsi.required' => 'Deskripsi wajib diisi.',
            'deskripsi.min' => 'Deskripsi minimal 10 karakter.',
            'cover.image' => 'Cover harus berupa gambar.',
            'cover.max' => 'Ukuran cover maksimal 4 MB.',
            'berkas.mimes' => 'Berkas ebook harus PDF.',
            'berkas.max' => 'Ukuran berkas maksimal 50 MB.',
        ];
    }

    public function kategori(): KategoriEbook
    {
        return KategoriEbook::from((string) $this->string('kategori'));
    }

    public function status(): StatusEbook
    {
        return StatusEbook::from((string) $this->string('status'));
    }

    public function cover(): ?UploadedFile
    {
        $berkas = $this->file('cover');

        return $berkas instanceof UploadedFile ? $berkas : null;
    }

    public function berkas(): ?UploadedFile
    {
        $berkas = $this->file('berkas');

        return $berkas instanceof UploadedFile ? $berkas : null;
    }

    public function jumlahHalaman(): ?int
    {
        return $this->filled('jumlahHalaman') ? $this->integer('jumlahHalaman') : null;
    }
}
