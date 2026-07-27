<?php

declare(strict_types=1);

namespace App\Http\Requests;

use BackedEnum;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Dasar seluruh endpoint daftar: pencarian, urutan, dan paginasi.
 *
 * `SEMUA` diperlakukan sebagai "tanpa filter" — nilai itu dipakai frontend
 * sebagai pilihan default di setiap <Select>, jadi ia sampai ke server apa
 * adanya dan lebih jujur ditangani di sini daripada disaring di 5 komponen.
 */
abstract class DaftarRequest extends FormRequest
{
    /** Batas atas yang wajar sekaligus rem: tabel meminta 1000 baris sekaligus
     *  karena paginasinya masih di sisi klien. */
    private const int MAKS_PER_HALAMAN = 1000;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    protected function aturanDaftar(): array
    {
        return [
            'cari' => ['nullable', 'string', 'max:120'],
            'halaman' => ['nullable', 'integer', 'min:1'],
            'perHalaman' => ['nullable', 'integer', 'min:1', 'max:'.self::MAKS_PER_HALAMAN],
            'urutKolom' => ['nullable', 'string', 'max:40'],
            'urutArah' => ['nullable', 'in:asc,desc'],
        ];
    }

    public function halaman(): int
    {
        return (int) $this->integer('halaman', 1);
    }

    public function perHalaman(int $bawaan = 10): int
    {
        return (int) $this->integer('perHalaman', $bawaan);
    }

    public function cari(): ?string
    {
        $cari = trim((string) $this->string('cari'));

        return $cari === '' ? null : $cari;
    }

    public function urutKolom(string $bawaan): string
    {
        $kolom = (string) $this->string('urutKolom');

        return $kolom === '' ? $bawaan : $kolom;
    }

    /**
     * @param  'asc'|'desc'  $bawaan
     * @return 'asc'|'desc'
     */
    public function urutArah(string $bawaan = 'desc'): string
    {
        return match ((string) $this->string('urutArah')) {
            'asc' => 'asc',
            'desc' => 'desc',
            default => $bawaan,
        };
    }

    /**
     * Baca sebuah filter enum. `SEMUA`, kosong, atau tidak dikirim → null.
     *
     * @template TEnum of BackedEnum
     *
     * @param  class-string<TEnum>  $enum
     * @return TEnum|null
     */
    protected function filterEnum(string $kunci, string $enum): ?BackedEnum
    {
        $nilai = (string) $this->string($kunci);

        if ($nilai === '' || $nilai === 'SEMUA') {
            return null;
        }

        return $enum::tryFrom($nilai);
    }

    /**
     * Aturan validasi untuk filter enum yang menerima `SEMUA`.
     *
     * @param  class-string<BackedEnum>  $enum
     * @return array<int, mixed>
     */
    protected function aturanFilterEnum(string $enum): array
    {
        $nilai = array_map(static fn (BackedEnum $kasus): string => (string) $kasus->value, $enum::cases());

        return ['nullable', 'string', 'in:SEMUA,'.implode(',', $nilai)];
    }
}
