<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PosUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pastikan yang masuk benar-benar pemilik toko, dan tokonya tidak ditangguhkan.
 *
 * Dua pemeriksaan, dua alasan berbeda:
 *
 * 1. **Bukan PosUser → 403.** Guard `pos` mencoba guard sesi lebih dulu
 *    (bawaan `sanctum.guard`), jadi admin yang kebetulan sedang punya cookie
 *    sesi di peramban yang sama akan lolos autentikasi sebagai dirinya sendiri.
 *    Ia tidak punya toko, dan setiap kueri di belakang sini menganggap ada.
 *
 * 2. **Ditangguhkan → 403.** Penangguhan adalah keputusan admin platform;
 *    seluruh aplikasi berhenti, termasuk halaman langganan. Yang kedaluwarsa
 *    ditangani terpisah oleh PastikanLanggananBerjalan, karena mereka justru
 *    masih butuh halaman pembayaran untuk keluar dari keadaannya.
 */
class PastikanPosUser
{
    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user();

        if (! $pengguna instanceof PosUser) {
            return response()->json([
                'message' => 'Akun ini bukan pemilik toko.',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($pengguna->ditangguhkan) {
            return response()->json([
                'message' => 'Akun toko ditangguhkan. Hubungi dukungan Jualin Aja.',
                'alasan' => $pengguna->alasan_penangguhan,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
