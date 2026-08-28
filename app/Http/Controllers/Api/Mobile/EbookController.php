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
        $boleh = $toko->bolehAksesResep();

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

        if (! $toko->bolehAksesResep()) {
            return response()->json([
                'message' => 'Akses resep hanya tersedia untuk paket Langganan. Tingkatkan paket untuk mengunduh resep.',
                'kode' => 'LANGGANAN_KEDALUWARSA',
            ], Response::HTTP_PAYMENT_REQUIRED);
        }

        if ($ebook->status !== StatusEbook::Terbit || $ebook->berkas_path === null) {
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

        return response()->json(['fileUrl' => $this->urlBerkas($ebook->berkas_path, $request)]);
    }

    /**
     * URL berkas relatif terhadap host yang mengirim permintaan.
     *
     * `Storage::disk('public')->url()` memakai `APP_URL` (= localhost:8000),
     * yang benar untuk panel admin tapi salah bagi aplikasi POS yang datang
     * lewat ngrok: `localhost` di perangkat itu menunjuk ke perangkatnya
     * sendiri. Memakai `$request->root()` membuat tautan selalu cocok dengan
     * host yang dipakai aplikasi memanggil.
     */
    private function urlBerkas(?string $path, Request $request): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim((string) $request->root(), '/').'/uploads/'.ltrim($path, '/');
    }
}
