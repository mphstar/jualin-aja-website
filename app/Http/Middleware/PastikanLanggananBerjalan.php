<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PosUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kunci rute operasional saat langganan sudah kedaluwarsa.
 *
 * Dipasang HANYA pada kasir, produk, kategori, dan laporan — bukan pada
 * seluruh grup. Halaman langganan, riwayat tagihan, dan pembuatan tagihan
 * harus tetap terbuka: aplikasi yang mengunci pintu keluarnya sendiri adalah
 * aplikasi yang tidak bisa diperpanjang, dan pemilik toko yang lupa membayar
 * akan berakhir menelepon dukungan untuk sesuatu yang seharusnya bisa ia
 * selesaikan sendiri dalam dua ketukan.
 *
 * Membaca tetap diizinkan; yang ditolak hanya penulisan. Kasir yang tidak bisa
 * melihat daftar produknya sendiri kehilangan datanya di mata pemiliknya —
 * padahal datanya utuh, cuma langganannya habis.
 */
class PastikanLanggananBerjalan
{
    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var PosUser $toko */
        $toko = $request->user();

        if ($request->isMethodSafe() || $toko->langgananBerjalan()) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Langganan sudah berakhir. Perpanjang dulu untuk mencatat transaksi baru.',
            'kode' => 'LANGGANAN_KEDALUWARSA',
        ], Response::HTTP_PAYMENT_REQUIRED);
    }
}
