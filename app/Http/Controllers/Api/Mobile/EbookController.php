<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\StatusEbook;
use App\Http\Controllers\Controller;
use App\Http\Resources\Pos\EbookPosResource;
use App\Models\Ebook;
use App\Models\UnduhanEbook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Katalog ebook resep (PRD §4.3).
 *
 * Seluruh ebook TERBIT terbuka untuk langganan yang masih berjalan. Tidak ada
 * pemberian akses per-ebook — sederhana, dan justru itu yang jadi nilai jual
 * langganannya.
 */
class EbookController extends Controller
{
    use MilikToko;

    public function index(Request $request): AnonymousResourceCollection
    {
        $toko = $this->toko($request);
        $boleh = $toko->langgananBerjalan();

        $daftar = Ebook::query()
            ->where('status', StatusEbook::Terbit->value)
            ->orderByDesc('tanggal_terbit')
            ->orderByDesc('id')
            ->get();

        return EbookPosResource::collection(
            $daftar->map(fn (Ebook $e): EbookPosResource => new EbookPosResource($e, $boleh)),
        );
    }

    /**
     * Catat unduhan lalu kembalikan tautan berkasnya.
     *
     * Pemeriksaan langganan ada DI SINI, bukan hanya di daftar. Tautan yang
     * pernah terlihat saat langganan masih hidup akan tetap tersimpan di
     * perangkat, dan tanpa gerbang di titik unduh ia bisa dipakai selamanya.
     */
    public function unduh(Request $request, Ebook $ebook): JsonResponse
    {
        $toko = $this->toko($request);

        if (! $toko->langgananBerjalan()) {
            return response()->json([
                'message' => 'Langganan sudah berakhir. Perpanjang untuk mengunduh resep.',
                'kode' => 'LANGGANAN_KEDALUWARSA',
            ], Response::HTTP_PAYMENT_REQUIRED);
        }

        if ($ebook->status !== StatusEbook::Terbit || $ebook->berkasUrl() === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        UnduhanEbook::query()->create([
            'ebook_id' => $ebook->id,
            'pos_user_id' => $toko->id,
            'tanggal' => now(),
        ]);

        // Penghitung didenormalisasi untuk tabel admin; dinaikkan lewat
        // `increment` supaya dua unduhan bersamaan tidak saling menimpa.
        $ebook->increment('jumlah_unduhan');

        return response()->json(['fileUrl' => $ebook->berkasUrl()]);
    }
}
