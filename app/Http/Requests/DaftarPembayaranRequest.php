<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use Carbon\CarbonImmutable;

class DaftarPembayaranRequest extends DaftarRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...$this->aturanDaftar(),
            'status' => $this->aturanFilterEnum(StatusPembayaran::class),
            'metode' => $this->aturanFilterEnum(MetodePembayaran::class),
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ];
    }

    public function status(): ?StatusPembayaran
    {
        return $this->filterEnum('status', StatusPembayaran::class);
    }

    public function metode(): ?MetodePembayaran
    {
        return $this->filterEnum('metode', MetodePembayaran::class);
    }

    public function dari(): ?CarbonImmutable
    {
        $nilai = (string) $this->string('dari');

        return $nilai === '' ? null : CarbonImmutable::parse($nilai)->startOfDay();
    }

    /**
     * Batas atas dibulatkan ke akhir hari: filter tanggal di UI memakai
     * <input type="date">, jadi "sampai 27 Juli" harus ikut memuat transaksi
     * pukul 27 Juli 23:59 — bukan berhenti di tengah malam.
     */
    public function sampai(): ?CarbonImmutable
    {
        $nilai = (string) $this->string('sampai');

        return $nilai === '' ? null : CarbonImmutable::parse($nilai)->endOfDay();
    }
}
