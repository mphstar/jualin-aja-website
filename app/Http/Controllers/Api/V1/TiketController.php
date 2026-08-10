<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\JenisTiket;
use App\Enums\StatusTiket;
use App\Http\Controllers\Controller;
use App\Models\TiketDukungan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TiketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TiketDukungan::query()->with(['posUser', 'admin']);

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->string('jenis')->value());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        if ($request->filled('cari')) {
            $cari = '%' . $request->string('cari')->value() . '%';
            $query->where(function ($q) use ($cari): void {
                $q->where('nomor_tiket', 'like', $cari)
                    ->orWhere('subjek', 'like', $cari)
                    ->orWhere('pesan', 'like', $cari)
                    ->orWhereHas('posUser', function ($qu) use ($cari): void {
                        $qu->where('nama_toko', 'like', $cari)
                            ->orWhere('nama', 'like', $cari)
                            ->orWhere('email', 'like', $cari);
                    });
            });
        }

        $daftar = $query->orderByDesc('id')->paginate(15);

        $daftar->getCollection()->transform(fn (TiketDukungan $t): array => $this->formatAdminTiket($t));

        return response()->json($daftar);
    }

    public function show(TiketDukungan $tiket): JsonResponse
    {
        $tiket->load(['posUser', 'admin']);

        return response()->json($this->formatAdminTiket($tiket));
    }

    public function respon(Request $request, TiketDukungan $tiket): JsonResponse
    {
        $data = $request->validate([
            'balasan' => ['required', 'string', 'max:5000'],
            'status' => ['nullable', 'string', Rule::enum(StatusTiket::class)],
        ]);

        $tiket->update([
            'balasan_admin' => $data['balasan'],
            'dibalas_pada' => now(),
            'dibalas_oleh_id' => $request->user()?->id,
            'status' => $data['status'] ?? StatusTiket::Selesai->value,
        ]);

        $tiket->load(['posUser', 'admin']);

        return response()->json($this->formatAdminTiket($tiket));
    }

    public function ubahStatus(Request $request, TiketDukungan $tiket): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', Rule::enum(StatusTiket::class)],
        ]);

        $tiket->update([
            'status' => $data['status'],
        ]);

        return response()->json($this->formatAdminTiket($tiket));
    }

    /** @return array<string, mixed> */
    private function formatAdminTiket(TiketDukungan $t): array
    {
        return [
            'id' => $t->id,
            'nomorTiket' => $t->nomor_tiket,
            'jenis' => $t->jenis->value,
            'jenisLabel' => $t->jenis->label(),
            'subjek' => $t->subjek,
            'pesan' => $t->pesan,
            'status' => $t->status->value,
            'statusLabel' => $t->status->label(),
            'prioritas' => $t->prioritas->value,
            'prioritasLabel' => $t->prioritas->label(),
            'balasanAdmin' => $t->balasan_admin,
            'dibalasPada' => $t->dibalas_pada?->toIso8601String(),
            'adminNama' => $t->admin?->name,
            'toko' => [
                'id' => $t->posUser->id,
                'nama' => $t->posUser->nama,
                'email' => $t->posUser->email,
                'namaToko' => $t->posUser->nama_toko,
                'jenisUsaha' => $t->posUser->jenis_usaha->label(),
            ],
            'dibuatPada' => $t->created_at->toIso8601String(),
        ];
    }
}
