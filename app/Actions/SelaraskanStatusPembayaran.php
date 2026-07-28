<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StatusPembayaran;
use App\Models\Pembayaran;
use Illuminate\Support\Facades\DB;

/**
 * Terjemahkan `transaction_status` Midtrans jadi status invoice kita, lalu
 * teruskan ke Action pelunasan / kegagalan yang sudah ada.
 *
 * Satu tempat untuk dua jalur masuk yang berbeda — webhook dan tombol "Saya
 * sudah bayar" — karena keduanya membawa payload dengan bentuk yang sama, dan
 * dua penerjemah berarti dua peluang untuk menganggap `settlement` berbeda dari
 * `capture`.
 *
 * **Idempoten.** Midtrans mengirim ulang notifikasi yang tidak dijawab 200, dan
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
            (string) ($payload['fraud_status'] ?? 'accept'),
        );

        /*
         * Jejak mentahnya disimpan lebih dulu dan selalu — termasuk saat
         * statusnya tidak berubah. Ketika nanti ada sengketa "saya sudah
         * bayar", yang menyelesaikannya adalah payload apa adanya dari
         * gerbang, bukan ringkasan yang sudah kita tafsirkan.
         */
        DB::transaction(function () use ($pembayaran, $payload): void {
            $pembayaran->update([
                'midtrans_payload' => $payload,
                'midtrans_transaction_id' => $payload['transaction_id']
                    ?? $pembayaran->midtrans_transaction_id,
            ]);
        }, attempts: 3);

        if ($tujuan === null || $tujuan === $pembayaran->status) {
            return $pembayaran->refresh();
        }

        // Invoice yang sudah lunas tidak pernah turun statusnya. Notifikasi
        // `expire` yang menyusul setelah settlement adalah hal yang benar-benar
        // terjadi, dan menurutinya berarti mencabut langganan yang sudah dibayar.
        if ($pembayaran->status === StatusPembayaran::Lunas) {
            return $pembayaran;
        }

        return match ($tujuan) {
            StatusPembayaran::Lunas => ($this->tandaiLunas)($pembayaran),
            StatusPembayaran::Gagal,
            StatusPembayaran::Kedaluwarsa => ($this->tandaiGagal)($pembayaran, $tujuan),
            default => $pembayaran->refresh(),
        };
    }

    /**
     * Peta status Midtrans → status invoice.
     *
     * `capture` hanya lunas kalau lolos pemeriksaan penipuan; `challenge`
     * berarti Midtrans meminta merchant memutuskan sendiri, dan menganggapnya
     * lunas otomatis adalah cara paling cepat kehilangan uang.
     *
     * Null berarti "belum ada keputusan" — invoice tetap menunggu.
     */
    public static function terjemahkan(string $status, string $fraud = 'accept'): ?StatusPembayaran
    {
        return match ($status) {
            'settlement' => StatusPembayaran::Lunas,
            'capture' => $fraud === 'accept' ? StatusPembayaran::Lunas : null,
            'expire' => StatusPembayaran::Kedaluwarsa,
            'deny', 'cancel', 'failure' => StatusPembayaran::Gagal,
            'refund', 'partial_refund', 'chargeback' => StatusPembayaran::Refund,
            default => null,
        };
    }
}
