<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Pembayaran;
use Carbon\CarbonImmutable;

/**
 * Nomor invoice berurut per tahun: `INV/2026/0001`.
 *
 * Diambil dari `MAX` di dalam transaksi yang sedang berjalan, dengan seluruh
 * baris tahun berjalan dikunci. Berbeda dengan nomor struk yang penghitungnya
 * disimpan per toko, invoice berlaku global dan jumlahnya jauh lebih sedikit —
 * puluhan per hari, bukan ratusan per jam — sehingga mengunci rentang tahun
 * tidak menghambat siapa pun.
 */
final readonly class NomorInvoiceBerikutnya
{
    public function __invoke(?CarbonImmutable $sekarang = null): string
    {
        $sekarang ??= CarbonImmutable::now();
        $tahun = (string) $sekarang->year;
        $awalan = sprintf('INV/%s/', $tahun);

        /** @var string|null $terakhir */
        $terakhir = Pembayaran::query()
            ->where('nomor_invoice', 'like', $awalan.'%')
            ->lockForUpdate()
            ->max('nomor_invoice');

        $urutan = $terakhir === null
            ? 1
            : ((int) mb_substr($terakhir, mb_strlen($awalan))) + 1;

        /*
         * Tabrakan tetap mungkin kalau ada baris yang dibuat di luar action ini
         * (seeder, impor). Naik satu sampai bebas jauh lebih murah daripada
         * membiarkan unique constraint melempar galat ke pemilik toko yang
         * sedang berusaha membayar.
         */
        while (Pembayaran::query()->where('nomor_invoice', $this->susun($awalan, $urutan))->exists()) {
            $urutan++;
        }

        return $this->susun($awalan, $urutan);
    }

    private function susun(string $awalan, int $urutan): string
    {
        return $awalan.str_pad((string) $urutan, 4, '0', STR_PAD_LEFT);
    }
}
