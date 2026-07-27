<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\DurasiPaket;
use App\Enums\JenisAksi;
use App\Enums\TargetAksi;
use App\Http\Controllers\Controller;
use App\Http\Requests\SimpanHargaPaketRequest;
use App\Models\Pengaturan;
use App\Services\PencatatAktivitas;
use App\Support\Format;

class PengaturanController extends Controller
{
    /** Harga contoh awal (PRD §4.1) — hanya dipakai bila belum pernah diubah. */
    private const array HARGA_BAWAAN = [
        'TRIAL' => 0,
        'BULANAN' => 99_000,
        'SEMESTERAN' => 499_000,
        'TAHUNAN' => 899_000,
    ];

    public function __construct(private readonly PencatatAktivitas $pencatat) {}

    /** @return array<string, int> */
    public function hargaPaket(): array
    {
        return $this->hargaTersimpan();
    }

    /** @return array<string, int> */
    public function simpanHargaPaket(SimpanHargaPaketRequest $request): array
    {
        $sebelumnya = $this->hargaTersimpan();
        $baru = $request->hargaPaket();

        Pengaturan::simpan(Pengaturan::KUNCI_HARGA_PAKET, $baru);

        $berubah = [];
        foreach (DurasiPaket::berbayar() as $durasi) {
            $lama = $sebelumnya[$durasi->value] ?? 0;
            if ($lama !== $baru[$durasi->value]) {
                $berubah[] = sprintf(
                    '%s %s → %s',
                    $durasi->label(),
                    Format::rupiah($lama),
                    Format::rupiah($baru[$durasi->value]),
                );
            }
        }

        // Menyimpan tanpa perubahan bukan peristiwa — mencatatnya hanya
        // membanjiri log dengan baris yang tidak memberi tahu apa pun.
        if ($berubah !== []) {
            $this->pencatat->catat(
                aksi: JenisAksi::PengaturanUbah,
                targetTipe: TargetAksi::Sistem,
                deskripsi: 'Mengubah harga paket: '.implode(', ', $berubah).'.',
            );
        }

        return $baru;
    }

    /** @return array<string, int> */
    private function hargaTersimpan(): array
    {
        /** @var array<string, int> $harga */
        $harga = Pengaturan::ambil(Pengaturan::KUNCI_HARGA_PAKET, self::HARGA_BAWAAN);

        return [...self::HARGA_BAWAAN, ...$harga];
    }
}
