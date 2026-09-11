<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\DurasiPaket;
use App\Enums\JenisAksi;
use App\Enums\TargetAksi;
use App\Http\Controllers\Controller;
use App\Http\Requests\SimpanHargaPaketRequest;
use App\Http\Requests\SimpanPengaturanMayarRequest;
use App\Models\Pengaturan;
use App\Services\PencatatAktivitas;
use App\Support\Format;
use App\Support\HargaPaket;
use App\Support\KonfigurasiMayar;

class PengaturanController extends Controller
{
    public function __construct(private readonly PencatatAktivitas $pencatat) {}

    /** @return array<string, int> */
    public function hargaPaket(): array
    {
        return HargaPaket::semua();
    }

    /** @return array<string, int> */
    public function simpanHargaPaket(SimpanHargaPaketRequest $request): array
    {
        $sebelumnya = HargaPaket::semua();
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

    /** @return array<string, mixed> */
    public function mayar(): array
    {
        return KonfigurasiMayar::semua();
    }

    /** @return array<string, mixed> */
    public function simpanMayar(SimpanPengaturanMayarRequest $request): array
    {
        $sebelumnya = KonfigurasiMayar::semua();
        $baru = array_merge($sebelumnya, $request->konfigurasi());

        Pengaturan::simpan(Pengaturan::KUNCI_MAYAR, $baru);

        $berubah = [];
        if ($sebelumnya['is_production'] !== $baru['is_production']) {
            $berubah[] = 'mode '.$this->labelMode($sebelumnya['is_production'])
                .' → '.$this->labelMode($baru['is_production']);
        }

        if (($sebelumnya['api_key'] ?? '') !== $baru['api_key']) {
            $berubah[] = 'API key';
        }

        if (($sebelumnya['webhook_secret'] ?? '') !== ($baru['webhook_secret'] ?? '')) {
            $berubah[] = 'webhook secret';
        }

        if (($sebelumnya['timeout'] ?? null) !== $baru['timeout']) {
            $berubah[] = 'batas waktu';
        }

        if ($berubah !== []) {
            $this->pencatat->catat(
                aksi: JenisAksi::PengaturanUbah,
                targetTipe: TargetAksi::Sistem,
                deskripsi: 'Mengubah pengaturan pembayaran Mayar: '.implode(', ', $berubah).'.',
            );
        }

        return $baru;
    }

    private function labelMode(mixed $produksi): string
    {
        return $produksi ? 'produksi' : 'sandbox';
    }
}
