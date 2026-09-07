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
 * Penerima notifikasi Mayar — petunjuk, bukan bukti.
 *
 * Webhook Mayar tidak ditandatangani. Perlindungan ingrees-nya:
 *
 * 1. Rahasia panjang di dalam path URL, dibandingkan konstan (`hash_equals`).
 * 2. Batas ukuran badan — diukur dari panjang byte, bukan string.
 * 3. Rate limit di route (`throttle:30,1`).
 *
 * Dan yang paling penting: meski bentuk notifikasinya sah, statusnya TIDAK
 * dipercaya begitu saja. Controller membaca ulang `GET /transactions/{id}` dan
 * hanya menuruti status `paid`. Webhook hanyalah pemicu; transaksi detail yang
 * menjadi bukti.
 *
 * **Selalu menjawab 200 untuk notifikasi yang sah** — termasuk untuk invoice
 * yang tidak dikenali. Mayar mengirim ulang apa pun yang tidak dijawab 200;
 * untuk invoice yang memang tidak ada, pengiriman ulang tidak akan membuatnya
 * ada.
 */
class MayarController extends Controller
{
    /** Batas ukuran badan notifikasi, dalam byte. */
    private const int BATAS_BADAN = 64 * 1024;

    public function notifikasi(
        Request $request,
        string $rahasia,
        GerbangPembayaran $gerbang,
        SelaraskanStatusPembayaran $selaraskan,
    ): JsonResponse {
        if ($rahasia === '' || ! hash_equals((string) config('services.mayar.webhook_secret', ''), $rahasia)) {
            return response()->json(['message' => 'Tidak ketemu.'], Response::HTTP_NOT_FOUND);
        }

        // Diukur dari byte, bukan karakter — badan JSON berisi payload yang
        // tidak pernah sebesar ini.
        if (strlen($request->getContent()) > self::BATAS_BADAN) {
            Log::warning('Notifikasi Mayar terlalu besar.', ['pi' => $request->ip()]);

            return response()->json(['message' => 'Terlalu besar.'], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->all();

        if (! $gerbang->notifikasiSah($payload)) {
            Log::warning('Notifikasi Mayar dengan bentuk tidak sah ditolak.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Bentuk notifikasi tidak sah.'], Response::HTTP_FORBIDDEN);
        }

        // Hanya pembayaran yang selesai yang menyangkut kita; pengingat dan
        // peristiwa lain dijawab 200 tanpa melakukan apa-apa.
        if ((string) ($payload['event'] ?? '') !== 'payment.received') {
            return response()->json(['message' => 'Diterima.']);
        }

        /** @var array<string, mixed> $data */
        $data = (array) $payload['data'];

        $transactionId = (string) ($data['transactionId'] ?? $data['id'] ?? '');

        $pembayaran = Pembayaran::query()
            ->where('mayar_transaction_id', $transactionId)
            ->orWhere('mayar_order_id', $transactionId)
            ->first();

        if ($pembayaran === null) {
            Log::warning('Notifikasi Mayar untuk invoice yang tidak dikenali.', ['transaction_id' => $transactionId]);

            return response()->json(['message' => 'Invoice tidak dikenali.']);
        }

        /*
         * Baca ulang status dari Mayar sendiri. Notifikasi hanyalah petunjuk
         * bahwa sebuah transaksi selesai; yang membuktikannya adalah jawaban
         * `GET /transactions/{id}` dengan `status: paid`. `orderId` kita di
         * `extraData` ikut dibandingkan supaya satu pembayaran tidak mengunci
         * kunci dua invoice.
         */
        $status = $gerbang->periksaStatus($pembayaran);

        if (($status['transaction_status'] ?? '') !== 'paid') {
            return response()->json(['message' => 'Diterima.']);
        }

        $nominal = (int) ($status['gross_amount'] ?? 0);

        if ($nominal !== $pembayaran->nominal) {
            Log::error('Nominal transaksi Mayar tidak cocok dengan invoice.', [
                'invoice' => $pembayaran->nomor_invoice,
                'diharapkan' => $pembayaran->nominal,
                'diterima' => $nominal,
            ]);

            return response()->json(['message' => 'Nominal tidak cocok.'], Response::HTTP_CONFLICT);
        }

        $ekstra = $status['extraData'] ?? null;
        $orderId = is_array($ekstra) ? (string) ($ekstra['orderId'] ?? '') : '';

        if ($orderId !== '' && $orderId !== $pembayaran->nomor_invoice) {
            Log::warning('orderId di extraData tidak cocok dengan invoice.', [
                'invoice' => $pembayaran->nomor_invoice,
                'order_id' => $orderId,
            ]);

            return response()->json(['message' => 'Invoice tidak cocok.'], Response::HTTP_CONFLICT);
        }

        $selaraskan($pembayaran, $status);

        return response()->json(['message' => 'Diterima.']);
    }
}
