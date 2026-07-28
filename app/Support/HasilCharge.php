<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Instruksi pembayaran yang dijawab gerbang.
 *
 * Satu bentuk untuk semua saluran, dengan field yang tidak terpakai bernilai
 * null. Alternatifnya — satu kelas per saluran — memaksa setiap pemanggil
 * memeriksa tipe sebelum bisa membaca apa pun, padahal yang mereka butuhkan
 * cuma "apa yang harus saya tampilkan ke pengguna".
 */
final readonly class HasilCharge
{
    /** @param  array<string, mixed>  $payload */
    public function __construct(
        public string $transactionId,
        public string $orderId,
        public array $payload,
        public ?CarbonImmutable $batasBayar = null,
        public ?string $kodeBayar = null,
        public ?string $kodePerusahaan = null,
        public ?string $qrUrl = null,
        public ?string $tautanBayar = null,
    ) {}
}
