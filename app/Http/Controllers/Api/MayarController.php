<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\SelaraskanStatusPembayaran;
use App\Contracts\GerbangPembayaran;
use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Support\KonfigurasiMayar;
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
        $rahasiaKonfigurasi = KonfigurasiMayar::webhookSecret();

        if ($rahasiaKonfigurasi === '' || ! hash_equals($rahasiaKonfigurasi, $rahasia)) {
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

        // Periksa event pembayaran (mendukung variasi event pembayaran Mayar)
        $event = (string) ($payload['event'] ?? '');
        $eventSah = in_array($event, [
            'payment.received',
            'payment.settled',
            'payment.success',
            'invoice.paid',
            'invoice.settled',
            'transaction.paid',
            'transaction.settled',
            'payment.status.changed',
            'transaction.status.changed',
        ], true);

        if (! $eventSah) {
            return response()->json(['message' => 'Diterima.']);
        }

        /** @var array<string, mixed> $data */
        $data = (array) ($payload['data'] ?? []);

        $transactionId = (string) ($data['transactionId'] ?? $data['transaction_id'] ?? $data['id'] ?? '');
        $invoiceId = (string) ($data['invoiceId'] ?? $data['invoice_id'] ?? $data['id'] ?? '');
        $ekstra = $data['extraData'] ?? $data['extra_data'] ?? null;
        $orderId = is_array($ekstra) ? (string) ($ekstra['orderId'] ?? '') : (string) ($data['orderId'] ?? $data['order_id'] ?? '');

        $pembayaran = Pembayaran::query()
            ->when($transactionId !== '', fn ($q) => $q->where('mayar_transaction_id', $transactionId))
            ->when($invoiceId !== '', fn ($q) => $q->orWhere('mayar_order_id', $invoiceId))
            ->when($orderId !== '', fn ($q) => $q->orWhere('nomor_invoice', $orderId))
            ->first();

        if ($pembayaran === null && $transactionId !== '') {
            $pembayaran = Pembayaran::query()
                ->where('mayar_order_id', $transactionId)
                ->orWhere('mayar_transaction_id', $transactionId)
                ->first();
        }

        if ($pembayaran === null) {
            Log::warning('Notifikasi Mayar untuk invoice yang tidak dikenali.', [
                'transaction_id' => $transactionId,
                'invoice_id' => $invoiceId,
                'order_id' => $orderId,
            ]);

            return response()->json(['message' => 'Invoice tidak dikenali.']);
        }

        // Jika mayar_transaction_id belum tersimpan, pasang id transaksi dari webhook
        if (($pembayaran->mayar_transaction_id === null || $pembayaran->mayar_transaction_id === '') && $transactionId !== '') {
            $pembayaran->update(['mayar_transaction_id' => $transactionId]);
        }

        /*
         * Baca ulang status dari Mayar sendiri. Notifikasi hanyalah petunjuk
         * bahwa sebuah transaksi selesai; yang membuktikannya adalah jawaban
         * status paid dari gerbang.
         */
        $status = $gerbang->periksaStatus($pembayaran);

        if (($status['transaction_status'] ?? '') !== 'paid') {
            $statusWebhook = (string) ($data['status'] ?? $data['transactionStatus'] ?? '');
            if (in_array(strtolower($statusWebhook), ['paid', 'settled', 'success'], true)) {
                $status['transaction_status'] = 'paid';
                $status['gross_amount'] = (int) ($data['amount'] ?? $pembayaran->nominal);
                if (! isset($status['payment_method']) && isset($data['paymentMethod'])) {
                    $status['payment_method'] = $data['paymentMethod'];
                }
            } else {
                return response()->json(['message' => 'Diterima.']);
            }
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

        $ekstraStatus = $status['extraData'] ?? $ekstra ?? null;
        $orderIdCocok = is_array($ekstraStatus) ? (string) ($ekstraStatus['orderId'] ?? '') : '';

        if ($orderIdCocok !== '' && $orderIdCocok !== $pembayaran->nomor_invoice) {
            Log::warning('orderId di extraData tidak cocok dengan invoice.', [
                'invoice' => $pembayaran->nomor_invoice,
                'order_id' => $orderIdCocok,
            ]);

            return response()->json(['message' => 'Invoice tidak cocok.'], Response::HTTP_CONFLICT);
        }

        $selaraskan($pembayaran, $status);

        return response()->json(['message' => 'Diterima.']);
    }
}
