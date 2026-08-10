<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\JenisTiket;
use App\Enums\PrioritasTiket;
use App\Enums\StatusTiket;
use App\Http\Controllers\Controller;
use App\Models\TiketDukungan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class TiketController extends Controller
{
    use MilikToko;

    public function index(Request $request): JsonResponse
    {
        $toko = $this->toko($request);

        $daftar = TiketDukungan::query()
            ->where('pos_user_id', $toko->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (TiketDukungan $t): array => $this->formatTiket($t));

        return response()->json($daftar);
    }

    public function store(Request $request): JsonResponse
    {
        $toko = $this->toko($request);

        $data = $request->validate([
            'jenis' => ['required', 'string', Rule::enum(JenisTiket::class)],
            'subjek' => ['required', 'string', 'max:255'],
            'pesan' => ['required', 'string', 'max:5000'],
        ]);

        $tiket = TiketDukungan::query()->create([
            'pos_user_id' => $toko->id,
            'nomor_tiket' => TiketDukungan::buatNomorTiket(),
            'jenis' => $data['jenis'],
            'subjek' => $data['subjek'],
            'pesan' => $data['pesan'],
            'status' => StatusTiket::Terbuka->value,
            'prioritas' => PrioritasTiket::Sedang->value,
        ]);

        return response()->json($this->formatTiket($tiket), Response::HTTP_CREATED);
    }

    public function show(Request $request, TiketDukungan $tiket): JsonResponse
    {
        $toko = $this->toko($request);

        if ($tiket->pos_user_id !== $toko->id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return response()->json($this->formatTiket($tiket));
    }

    /** @return array<string, mixed> */
    private function formatTiket(TiketDukungan $t): array
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
            'dibuatPada' => $t->created_at->toIso8601String(),
        ];
    }
}
