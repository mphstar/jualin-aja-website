<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\StatusTransaksi;
use App\Models\PosUser;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Agregasi omzet — dihitung di basis data, bukan di PHP.
 *
 * Beranda dan Laporan sama-sama membutuhkannya, dan keduanya menjumlahkan hal
 * yang sama: `harga_satuan × jumlah` dari baris transaksi yang berstatus
 * selesai. Menariknya ke memori lalu menjumlahkan di PHP berjalan baik sampai
 * sebuah toko punya sepuluh ribu struk, dan setelah itu tidak pernah baik lagi.
 *
 * Yang dihitung hanya transaksi SELESAI. Piutang tidak masuk omzet — barangnya
 * memang sudah keluar, tapi uangnya belum ada.
 */
final class OmzetHarian
{
    /**
     * Total omzet, jumlah struk, dan jumlah item dalam sebuah rentang.
     *
     * @return array{omzet: int, transaksi: int, item: int}
     */
    public static function rentang(PosUser $toko, CarbonImmutable $dari, CarbonImmutable $sampai): array
    {
        /** @var object{omzet: int|string|null, transaksi: int|string|null, item: int|string|null} $baris */
        $baris = DB::table('transaksi')
            ->leftJoin('transaksi_baris', 'transaksi_baris.transaksi_id', '=', 'transaksi.id')
            ->where('transaksi.pos_user_id', $toko->id)
            ->where('transaksi.status', StatusTransaksi::Selesai->value)
            ->whereBetween('transaksi.waktu', [$dari, $sampai])
            ->selectRaw('COALESCE(SUM(transaksi_baris.harga_satuan * transaksi_baris.jumlah), 0) as omzet')
            ->selectRaw('COUNT(DISTINCT transaksi.id) as transaksi')
            ->selectRaw('COALESCE(SUM(transaksi_baris.jumlah), 0) as item')
            ->first();

        return [
            'omzet' => (int) ($baris->omzet ?? 0),
            'transaksi' => (int) ($baris->transaksi ?? 0),
            'item' => (int) ($baris->item ?? 0),
        ];
    }

    /**
     * Deret harian, SELENGKAP rentangnya — termasuk hari-hari nol.
     *
     * Grafik yang melompati hari sepi diam-diam membuat toko terlihat lebih
     * ramai daripada aslinya, dan garisnya menyambung dua hari yang sebenarnya
     * berjarak seminggu.
     *
     * @return list<array{tanggal: string, omzet: int, transaksi: int}>
     */
    public static function deret(PosUser $toko, CarbonImmutable $dari, CarbonImmutable $sampai): array
    {
        $terkumpul = DB::table('transaksi')
            ->leftJoin('transaksi_baris', 'transaksi_baris.transaksi_id', '=', 'transaksi.id')
            ->where('transaksi.pos_user_id', $toko->id)
            ->where('transaksi.status', StatusTransaksi::Selesai->value)
            ->whereBetween('transaksi.waktu', [$dari->startOfDay(), $sampai->endOfDay()])
            ->groupBy('hari')
            ->selectRaw(self::ekspresiHari())
            ->selectRaw('COALESCE(SUM(transaksi_baris.harga_satuan * transaksi_baris.jumlah), 0) as omzet')
            ->selectRaw('COUNT(DISTINCT transaksi.id) as transaksi')
            ->get()
            ->keyBy('hari');

        $deret = [];

        for ($hari = $dari->startOfDay(); $hari->lessThanOrEqualTo($sampai); $hari = $hari->addDay()) {
            $kunci = $hari->format('Y-m-d');
            /** @var object{omzet?: int|string, transaksi?: int|string}|null $baris */
            $baris = $terkumpul[$kunci] ?? null;

            $deret[] = [
                'tanggal' => $hari->toISOString(),
                'omzet' => (int) ($baris->omzet ?? 0),
                'transaksi' => (int) ($baris->transaksi ?? 0),
            ];
        }

        return $deret;
    }

    /**
     * Pemotong tanggal per mesin basis data.
     *
     * SQLite (dipakai uji dan pengembangan) dan MySQL (produksi) tidak punya
     * fungsi tanggal yang sama, dan `DATE()` MySQL memakai zona waktu server —
     * sementara nilai yang disimpan sudah dalam zona aplikasi.
     *
     * Dikembalikan sebagai ekspresi UTUH, bukan potongan yang disambung di
     * pemanggil: `selectRaw` hanya menerima literal-string, dan itu memang
     * pagar yang benar — SQL yang dirakit dari potongan adalah tempat injeksi
     * masuk.
     *
     * @return literal-string
     */
    private static function ekspresiHari(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', transaksi.waktu) as hari"
            : 'DATE(transaksi.waktu) as hari';
    }
}
