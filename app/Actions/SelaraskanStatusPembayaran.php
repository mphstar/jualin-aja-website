<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Models\Pembayaran;
use Illuminate\Support\Facades\DB;

/**
 * Terjemahkan status transaksi Mayar jadi status invoice kita, lalu teruskan
 * ke Action pelunasan / kegagalan yang sudah ada.
 *
 * Satu tempat untuk dua jalur masuk yang berbeda — webhook dan tombol "Saya
 * sudah bayar" — karena keduanya membawa bentuk yang sama: `transaction_status`
 * berisi status Mayar, `data` payload mentahnya.
 *
 * **Idempoten.** Mayar mengirim ulang notifikasi yang tidak dijawab 200, dan
 * pengguna bisa menekan tombol periksa berkali-kali. Invoice yang sudah lunas
 * dibiarkan apa adanya alih-alih diperpanjang dua kali.
 */
final readonly class SelaraskanStatusPembayaran
{
    public function __construct(
        private TandaiPembayaranLunas $tandaiLunas,
        private TandaiPembayaranGagal $tandaiGagal,
    ) {}

    /** @param  array<string, mixed>  $payload */
    public function __invoke(Pembayaran $pembayaran, array $payload): Pembayaran
    {
        $tujuan = self::terjemahkan(
            (string) ($payload['transaction_status'] ?? ''),
        );

        /*
         * Jejak mentahnya disimpan lebih dulu dan selalu — termasuk saat
         * statusnya tidak berubah. Ketika nanti ada sengketa "saya sudah
         * bayar", yang menyelesaikannya adalah payload apa adanya dari
         * gerbang, bukan ringkasan yang sudah kita tafsirkan.
         */
        DB::transaction(function () use ($pembayaran, $payload): void {
            $transactionId = (string) ($payload['transaction_id'] ?? $pembayaran->mayar_transaction_id ?? '');

            $pembayaran->update([
                'mayar_payload' => $payload['data'] ?? $payload,
                'mayar_transaction_id' => $transactionId !== ''
                    ? $transactionId
                    : $pembayaran->mayar_transaction_id,
            ]);
        }, attempts: 3);

        if ($tujuan === null || $tujuan === $pembayaran->status) {
            return $pembayaran->refresh();
        }

        // Invoice yang sudah lunas tidak pernah turun statusnya. Notifikasi
        // lain yang menyusul setelah settlement adalah hal yang benar-benar
        // terjadi, dan menurutinya berarti mencabut langganan yang sudah dibayar.
        if ($pembayaran->status === StatusPembayaran::Lunas) {
            return $pembayaran;
        }

        if ($tujuan === StatusPembayaran::Lunas) {
            $this->isiMetode($pembayaran, $payload);
        }

        return match ($tujuan) {
            StatusPembayaran::Lunas => ($this->tandaiLunas)($pembayaran),
            StatusPembayaran::Gagal,
            StatusPembayaran::Kedaluwarsa => ($this->tandaiGagal)($pembayaran, $tujuan),
            default => $pembayaran->refresh(),
        };
    }

    /**
     * Peta status Mayar → status invoice.
     *
     * `paid` satu-satunya yang lunas. `expired` dan `closed` berarti permintaan
     * ditutup tanpa pembayaran. Null berarti "belum ada keputusan" — invoice
     * tetap menunggu.
     */
    public static function terjemahkan(string $status): ?StatusPembayaran
    {
        return match ($status) {
            'paid', 'success' => StatusPembayaran::Lunas,
            'expired', 'closed' => StatusPembayaran::Kedaluwarsa,
            'canceled', 'cancelled', 'failed' => StatusPembayaran::Gagal,
            default => null,
        };
    }

    /**
     * Isi metode pembayaran sungguhan begitu pembayaran lunas.
     *
     * Saat tagihan dibuat, metodenya belum diketahui — pembayar memilihnya di
     * halaman Mayar. Payload pelunasan membawa `payment_method` (mis. QRIS),
     * yang dipetakan biar laporan admin tidak menumpuk semuanya di "Online".
     *
     * @param  array<string, mixed>  $payload
     */
    private function isiMetode(Pembayaran $pembayaran, array $payload): void
    {
        $metode = self::metodeDari(
            (string) ($payload['payment_method'] ?? $payload['data']['paymentMethod'] ?? ''),
        );

        if ($metode !== null && $metode !== $pembayaran->metode) {
            $pembayaran->update(['metode' => $metode]);
        }
    }

    private static function metodeDari(string $paymentMethod): ?MetodePembayaran
    {
        $v = mb_strtolower($paymentMethod);

        return match (true) {
            str_contains($v, 'qris') => MetodePembayaran::Qris,
            str_contains($v, 'gopay'), str_contains($v, 'dana'),
            str_contains($v, 'ovo'), str_contains($v, 'shopeepay') => MetodePembayaran::Ewallet,
            str_contains($v, 'bank'), str_contains($v, 'va'),
            str_contains($v, 'transfer'), str_contains($v, 'virtual') => MetodePembayaran::VirtualAccount,
            default => null,
        };
    }
}
