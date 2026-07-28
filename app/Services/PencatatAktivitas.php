<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\JenisAksi;
use App\Enums\TargetAksi;
use App\Models\LogAktivitas;
use App\Models\User;
use App\Support\NamaAktor;
use Illuminate\Contracts\Auth\Authenticatable;
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
        ?Authenticatable $aktor = null,
    ): LogAktivitas {
        /*
         * `Auth::user()` mengembalikan siapa pun yang dipegang guard AKTIF —
         * dan middleware `auth:pos` menjadikan guard `pos` sebagai guard
         * bawaan begitu autentikasinya lolos. Jadi pada permintaan dari
         * aplikasi POS, yang sampai ke sini adalah PosUser, bukan admin.
         *
         * Itu bukan hal yang perlu ditolak — pemilik toko yang memperpanjang
         * langganannya sendiri memang aktor yang sah. Yang tidak boleh terjadi
         * cuma memperlakukannya seperti admin: `aktor_id` menunjuk tabel
         * `users`, dan PosUser tidak punya kolom `name` sama sekali.
         */
        $aktor ??= Auth::user();

        return LogAktivitas::query()->create([
            'waktu' => now(),
            'aktor_id' => $aktor instanceof User ? $aktor->id : null,
            // Nama disalin, bukan direlasikan: entri lama harus tetap terbaca
            // apa adanya meski admin berganti nama atau dihapus.
            'aktor_nama' => NamaAktor::dari($aktor),
            'aksi' => $aksi,
            'target_tipe' => $targetTipe,
            'target_id' => $targetId,
            'target_label' => $targetLabel,
            'deskripsi' => $deskripsi,
        ]);
    }
}
