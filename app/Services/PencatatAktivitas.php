<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\JenisAksi;
use App\Enums\TargetAksi;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Pencatat jejak audit terpusat (PRD §F7.5).
 *
 * SETIAP mutasi wajib lewat sini, dan pemanggilnya selalu Action — bukan
 * controller. Kalau pencatatan ditaruh di controller, satu jalur yang terlewat
 * (perpanjangan otomatis dari pelunasan invoice, misalnya) membuat seluruh
 * jejaknya bohong tanpa ada yang menyadari.
 */
final class PencatatAktivitas
{
    public function catat(
        JenisAksi $aksi,
        TargetAksi $targetTipe,
        string $deskripsi,
        ?string $targetId = null,
        ?string $targetLabel = null,
        ?User $aktor = null,
    ): LogAktivitas {
        $aktor ??= Auth::user();

        return LogAktivitas::query()->create([
            'waktu' => now(),
            'aktor_id' => $aktor?->id,
            // Nama disalin, bukan direlasikan: entri lama harus tetap terbaca
            // apa adanya meski admin berganti nama atau dihapus.
            'aktor_nama' => $aktor !== null ? $aktor->name : 'Sistem',
            'aksi' => $aksi,
            'target_tipe' => $targetTipe,
            'target_id' => $targetId,
            'target_label' => $targetLabel,
            'deskripsi' => $deskripsi,
        ]);
    }
}
