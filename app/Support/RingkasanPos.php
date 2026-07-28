<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\StatusTransaksi;
use App\Models\Kategori;
use App\Models\PosUser;
use App\Models\Produk;
use App\Models\Transaksi;
use Carbon\CarbonImmutable;

/**
 * Seberapa hidup kasir sebuah toko — dilihat dari panel admin.
 *
 * Ini yang membedakan "pelanggan yang membayar" dari "pelanggan yang benar-
 * benar memakai". Toko yang langganannya aktif tapi nol transaksi selama 30
 * hari adalah toko yang tidak akan memperpanjang, dan itu baru terlihat kalau
 * angkanya ada di halaman yang sama dengan tanggal berakhirnya.
 */
final class RingkasanPos
{
    /**
     * @return array{
     *     kategori: int,
     *     produk: int,
     *     produkHabis: int,
     *     transaksi30Hari: int,
     *     omzet30Hari: int,
     *     piutangJumlah: int,
     *     piutangTotal: int,
     *     transaksiTerakhir: string|null,
     *     aktif: bool,
     * }
     */
    public static function untuk(PosUser $toko): array
    {
        $sekarang = CarbonImmutable::now();
        $tigaPuluhHari = OmzetHarian::rentang($toko, $sekarang->subDays(29)->startOfDay(), $sekarang->endOfDay());

        $piutang = Transaksi::query()
            ->milik($toko)
            ->where('transaksi.status', StatusTransaksi::Ditahan->value)
            ->with('baris')
            ->get();

        $terakhir = Transaksi::query()
            ->milik($toko)
            ->orderByDesc('waktu')
            ->value('waktu');

        return [
            'kategori' => Kategori::query()->milik($toko)->count(),
            'produk' => Produk::query()->milik($toko)->count(),
            'produkHabis' => Produk::query()->milik($toko)
                ->where('lacak_stok', true)->where('stok', '<=', 0)->count(),
            'transaksi30Hari' => $tigaPuluhHari['transaksi'],
            'omzet30Hari' => $tigaPuluhHari['omzet'],
            'piutangJumlah' => $piutang->count(),
            'piutangTotal' => $piutang->sum(fn (Transaksi $t): int => $t->total()),
            'transaksiTerakhir' => $terakhir instanceof CarbonImmutable
                ? $terakhir->toISOString()
                : ($terakhir === null ? null : CarbonImmutable::parse((string) $terakhir)->toISOString()),
            // "Aktif" di sini berarti kasirnya dipakai, bukan langganannya
            // berjalan — dua hal yang sering dikira sama sampai ada toko yang
            // membayar setahun lalu tidak pernah membuka aplikasinya.
            'aktif' => $tigaPuluhHari['transaksi'] > 0,
        ];
    }
}
