<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Instruksi pembayaran yang dijawab gerbang.
 *
 * Satu bentuk untuk semua saluran, dengan field yang tidak terpakai bernilai
 * null. `instruksi` adalah instrumen bayar yang sudah dinormalisasi sendiri
 * (lihat parser paymentDetail di MayarGerbang), bukan objek biasa gerbang.
 */
final readonly class HasilCharge
{
    /** @param  array<string, mixed>  $payload */
    public function __construct(
        public string $transactionId,
        public string $orderId,
        public array $payload,
        public ?CarbonImmutable $batasBayar = null,
        public ?CarbonImmutable $kedaluwarsaSaluran = null,
        public ?string $kodeBayar = null,
        public ?string $kodePerusahaan = null,
        public ?string $qrUrl = null,
        public ?string $tautanBayar = null,
        /** @var array<string, mixed>|null */
        public ?array $instruksi = null,
    ) {}
}
