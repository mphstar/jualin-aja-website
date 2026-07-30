<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\StatusTransaksi;
use App\Models\PosUser;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Agregasi omzet — dihitung di basis data, bukan di PHP.
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
     * Deret agregasi (harian, mingguan, atau bulanan).
     *
     * @return list<array{tanggal: string, omzet: int, transaksi: int, label?: string}>
     */
    public static function deret(PosUser $toko, CarbonImmutable $dari, CarbonImmutable $sampai, string $periode = 'TUJUH_HARI'): array
    {
        if ($periode === 'TAHUNAN') {
            return self::deretTahunan($toko, $dari, $sampai);
        }

        if ($periode === 'TIGA_PULUH_HARI') {
            return self::deretBulanan($toko, $dari, $sampai);
        }

        return self::deretHarian($toko, $dari, $sampai);
    }

    private static function deretHarian(PosUser $toko, CarbonImmutable $dari, CarbonImmutable $sampai): array
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

    private static function deretBulanan(PosUser $toko, CarbonImmutable $dari, CarbonImmutable $sampai): array
    {
        $deret = [];
        $tglAwal = $dari->startOfDay();
        $tglAkhir = $sampai->endOfDay();

        $mingguKe = 1;
        for ($cur = $tglAwal; $cur->lessThanOrEqualTo($tglAkhir); $cur = $cur->addDays(7)) {
            $chunkEnd = $cur->addDays(6)->endOfDay();
            if ($chunkEnd->greaterThan($tglAkhir)) {
                $chunkEnd = $tglAkhir;
            }

            $ringkas = self::rentang($toko, $cur, $chunkEnd);
            $deret[] = [
                'tanggal' => $cur->toISOString(),
                'omzet' => $ringkas['omzet'],
                'transaksi' => $ringkas['transaksi'],
                'label' => "Mgu $mingguKe",
            ];
            $mingguKe++;
        }

        return $deret;
    }

    private static function deretTahunan(PosUser $toko, CarbonImmutable $dari, CarbonImmutable $sampai): array
    {
        $bulanNama = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $deret = [];
        $tahun = $dari->year;

        for ($m = 1; $m <= 12; $m++) {
            $start = CarbonImmutable::create($tahun, $m, 1)->startOfDay();
            $end = $start->endOfMonth()->endOfDay();

            $ringkas = self::rentang($toko, $start, $end);
            $deret[] = [
                'tanggal' => $start->toISOString(),
                'omzet' => $ringkas['omzet'],
                'transaksi' => $ringkas['transaksi'],
                'label' => $bulanNama[$m],
            ];
        }

        return $deret;
    }

    private static function ekspresiHari(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "STRFTIME('%Y-%m-%d', datetime(transaksi.waktu, 'localtime')) as hari"
            : 'DATE(transaksi.waktu) as hari';
    }
}
