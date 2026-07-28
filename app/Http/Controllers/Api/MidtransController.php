<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\SelaraskanStatusPembayaran;
use App\Contracts\GerbangPembayaran;
use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penerima notifikasi Midtrans — jalur UTAMA pelunasan langganan.
 *
 * Terbuka untuk publik, dan memang harus: Midtrans menembak alamat ini dari
 * servernya sendiri, tanpa sesi dan tanpa token. Yang membedakannya dari orang
 * lain hanyalah `signature_key`, jadi verifikasinya bukan kehati-hatian
 * tambahan melainkan satu-satunya autentikasi yang ada di sini.
 *
 * **Selalu menjawab 200 untuk notifikasi yang sah**, termasuk untuk invoice
 * yang tidak dikenali. Midtrans mengirim ulang apa pun yang tidak dijawab 200,
 * dengan jeda yang makin panjang, sampai berhari-hari — dan notifikasi untuk
 * invoice yang memang tidak ada di sini tidak akan pernah jadi ada hanya karena
 * dikirim ulang.
 */
class MidtransController extends Controller
{
    public function notifikasi(
        Request $request,
        GerbangPembayaran $gerbang,
        SelaraskanStatusPembayaran $selaraskan,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->all();

        if (! $gerbang->notifikasiSah($payload)) {
            Log::warning('Notifikasi Midtrans dengan tanda tangan tidak sah ditolak.', [
                'order_id' => $payload['order_id'] ?? null,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Tanda tangan tidak sah.'], Response::HTTP_FORBIDDEN);
        }

        $orderId = (string) ($payload['order_id'] ?? '');

        $pembayaran = Pembayaran::query()
            ->where('midtrans_order_id', $orderId)
            // Tagihan yang dibuat sebelum kolom order_id terisi tetap bisa
            // dikenali lewat nomor invoicenya.
            ->orWhere('nomor_invoice', $orderId)
            ->first();

        if ($pembayaran === null) {
            Log::warning('Notifikasi Midtrans untuk invoice yang tidak dikenali.', ['order_id' => $orderId]);

            return response()->json(['message' => 'Invoice tidak dikenali.']);
        }

        /*
         * Nominal ikut diperiksa. Tanda tangan sudah membuktikan pesannya dari
         * Midtrans, tapi ia dihitung dari `gross_amount` yang dikirim — jadi
         * yang belum terbukti adalah bahwa jumlahnya sama dengan yang kita
         * tagih. Kalau berbeda, sesuatu di sisi merchant salah konfigurasi, dan
         * memperpanjang langganan atas dasar itu berarti menutupinya.
         */
        $nominalNotifikasi = (int) round((float) ($payload['gross_amount'] ?? 0));

        if ($nominalNotifikasi !== $pembayaran->nominal) {
            Log::error('Nominal notifikasi Midtrans tidak cocok dengan invoice.', [
                'invoice' => $pembayaran->nomor_invoice,
                'diharapkan' => $pembayaran->nominal,
                'diterima' => $nominalNotifikasi,
            ]);

            return response()->json(['message' => 'Nominal tidak cocok.'], Response::HTTP_CONFLICT);
        }

        $selaraskan($pembayaran, $payload);

        return response()->json(['message' => 'Diterima.']);
    }
}
