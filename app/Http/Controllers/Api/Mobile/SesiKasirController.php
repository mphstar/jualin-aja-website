<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\PosUser;
use App\Models\SesiKasir;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SesiKasirController extends Controller
{
    /** Format respons JSON sesi kasir untuk API Mobile */
    private function formatSesi(SesiKasir $sesi): array
    {
        return [
            'id' => $sesi->kode_sesi,
            'namaKasir' => $sesi->nama_kasir,
            'waktuBuka' => $sesi->waktu_buka->toIso8601String(),
            'waktuTutup' => $sesi->waktu_tutup?->toIso8601String(),
            'kasAwalTunai' => $sesi->kas_awal_tunai,
            'kasAwalQris' => $sesi->kas_awal_qris,
            'kasAwalTransfer' => $sesi->kas_awal_transfer,
            'totalTunai' => $sesi->total_tunai,
            'totalQris' => $sesi->total_qris,
            'totalTransfer' => $sesi->total_transfer,
            'jumlahTransaksi' => $sesi->jumlah_transaksi,
            'kasFisikTunai' => $sesi->kas_fisik_tunai,
            'kasFisikQris' => $sesi->kas_fisik_qris,
            'kasFisikTransfer' => $sesi->kas_fisik_transfer,
            'catatan' => $sesi->catatan,
        ];
    }

    public function aktif(Request $request): JsonResponse
    {
        /** @var PosUser $user */
        $user = $request->user();

        $aktif = SesiKasir::query()
            ->where('pos_user_id', $user->id)
            ->whereNull('waktu_tutup')
            ->latest('waktu_buka')
            ->first();

        return response()->json([
            'data' => $aktif ? $this->formatSesi($aktif) : null,
        ]);
    }

    public function buka(Request $request): JsonResponse
    {
        /** @var PosUser $user */
        $user = $request->user();

        $data = $request->validate([
            'namaKasir' => ['required', 'string', 'max:255'],
            'kasAwalTunai' => ['nullable', 'integer', 'min:0'],
            'kasAwalQris' => ['nullable', 'integer', 'min:0'],
            'kasAwalTransfer' => ['nullable', 'integer', 'min:0'],
        ]);

        // Jika ada sesi aktif yang belum ditutup, tutup otomatis
        SesiKasir::query()
            ->where('pos_user_id', $user->id)
            ->whereNull('waktu_tutup')
            ->update(['waktu_tutup' => now()]);

        $kode = 'SHIFT-' . now()->timestamp . '-' . rand(100, 999);

        $sesi = SesiKasir::create([
            'pos_user_id' => $user->id,
            'kode_sesi' => $kode,
            'nama_kasir' => $data['namaKasir'],
            'waktu_buka' => now(),
            'kas_awal_tunai' => $data['kasAwalTunai'] ?? 0,
            'kas_awal_qris' => $data['kasAwalQris'] ?? 0,
            'kas_awal_transfer' => $data['kasAwalTransfer'] ?? 0,
        ]);

        return response()->json([
            'data' => $this->formatSesi($sesi),
            'pesan' => 'Kasir berhasil dibuka',
        ], 201);
    }

    public function catatTransaksi(Request $request): JsonResponse
    {
        /** @var PosUser $user */
        $user = $request->user();

        $data = $request->validate([
            'metode' => ['required', 'string'],
            'total' => ['required', 'integer', 'min:0'],
        ]);

        $aktif = SesiKasir::query()
            ->where('pos_user_id', $user->id)
            ->whereNull('waktu_tutup')
            ->latest('waktu_buka')
            ->first();

        if ($aktif) {
            $metode = strtoupper($data['metode']);
            $total = (int) $data['total'];

            if ($metode === 'TUNAI') {
                $aktif->total_tunai += $total;
            } elseif ($metode === 'QRIS') {
                $aktif->total_qris += $total;
            } elseif ($metode === 'TRANSFER') {
                $aktif->total_transfer += $total;
            }

            $aktif->jumlah_transaksi += 1;
            $aktif->save();
        }

        return response()->json([
            'data' => $aktif ? $this->formatSesi($aktif) : null,
        ]);
    }

    public function tutup(Request $request): JsonResponse
    {
        /** @var PosUser $user */
        $user = $request->user();

        $data = $request->validate([
            'kasFisikTunai' => ['required', 'integer', 'min:0'],
            'kasFisikQris' => ['required', 'integer', 'min:0'],
            'kasFisikTransfer' => ['required', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $aktif = SesiKasir::query()
            ->where('pos_user_id', $user->id)
            ->whereNull('waktu_tutup')
            ->latest('waktu_buka')
            ->first();

        if (! $aktif) {
            return response()->json([
                'pesan' => 'Tidak ada sesi kasir aktif yang dapat ditutup',
            ], 422);
        }

        $aktif->update([
            'waktu_tutup' => now(),
            'kas_fisik_tunai' => $data['kasFisikTunai'],
            'kas_fisik_qris' => $data['kasFisikQris'],
            'kas_fisik_transfer' => $data['kasFisikTransfer'],
            'catatan' => $data['catatan'] ?? null,
        ]);

        return response()->json([
            'data' => $this->formatSesi($aktif),
            'pesan' => 'Kasir berhasil ditutup',
        ]);
    }

    public function riwayat(Request $request): JsonResponse
    {
        /** @var PosUser $user */
        $user = $request->user();

        $riwayat = SesiKasir::query()
            ->where('pos_user_id', $user->id)
            ->whereNotNull('waktu_tutup')
            ->latest('waktu_buka')
            ->take(50)
            ->get()
            ->map(fn (SesiKasir $s) => $this->formatSesi($s));

        return response()->json([
            'data' => $riwayat,
        ]);
    }
}
