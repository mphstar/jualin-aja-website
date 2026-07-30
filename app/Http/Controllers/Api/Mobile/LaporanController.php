<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\MetodeBayarPos;
use App\Enums\PeriodeLaporan;
use App\Enums\StatusTransaksi;
use App\Http\Controllers\Controller;
use App\Support\OmzetHarian;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Laporan penjualan satu toko.
 *
 * Seluruh angkanya DITURUNKAN dari transaksi — tidak ada satu pun yang
 * disimpan terpisah. Laporan yang angkanya ditulis di kolom sendiri tidak
 * pernah ketahuan salah sampai ada yang menghitung ulang dengan tangan.
 */
class LaporanController extends Controller
{
    use MilikToko;

    /** @return array<string, mixed> */
    public function __invoke(Request $request): array
    {
        $toko = $this->toko($request);
        $periodeVal = strtoupper((string) $request->string('periode', 'TUJUH_HARI'));
        if ($periodeVal === '') {
            $periodeVal = 'TUJUH_HARI';
        }

        if ($request->filled('dari') && $request->filled('sampai')) {
            try {
                $dari = CarbonImmutable::parse((string) $request->string('dari'))->startOfDay();
                $sampai = CarbonImmutable::parse((string) $request->string('sampai'))->endOfDay();
            } catch (\Throwable) {
                $sampai = CarbonImmutable::now()->endOfDay();
                $dari = $sampai->startOfDay()->subDays(6);
                $periodeVal = 'TUJUH_HARI';
            }
        } else {
            if ($periodeVal === 'TAHUNAN') {
                $sampai = CarbonImmutable::now()->endOfDay();
                $dari = CarbonImmutable::create($sampai->year, 1, 1)->startOfDay();
            } else if ($periodeVal === 'TIGA_PULUH_HARI') {
                $sampai = CarbonImmutable::now()->endOfDay();
                $dari = $sampai->startOfMonth()->startOfDay();
            } else {
                $periode = PeriodeLaporan::tryFrom($periodeVal) ?? PeriodeLaporan::TujuhHari;
                $sampai = CarbonImmutable::now()->endOfDay();
                $dari = $sampai->startOfDay()->subDays($periode->hari() - 1);
                $periodeVal = $periode->value;
            }
        }

        $periodeLabel = match ($periodeVal) {
            'HARI_INI' => $dari->translatedFormat('d M Y'),
            'TUJUH_HARI' => '7 hari',
            'TIGA_PULUH_HARI' => 'Bulan ' . $dari->translatedFormat('F Y'),
            'TAHUNAN' => 'Tahun ' . $dari->year,
            default => $dari->translatedFormat('d M Y') . ' - ' . $sampai->translatedFormat('d M Y'),
        };

        $ringkas = OmzetHarian::rentang($toko, $dari, $sampai);

        return [
            'periode' => $periodeVal,
            'periodeLabel' => $periodeLabel,
            'dari' => $dari->toISOString(),
            'sampai' => $sampai->toISOString(),
            ...$ringkas,
            'rataPerStruk' => $ringkas['transaksi'] === 0
                ? 0
                : intdiv($ringkas['omzet'], $ringkas['transaksi']),
            'harian' => OmzetHarian::deret($toko, $dari, $sampai, $periodeVal),
            'terlaris' => $this->terlaris($toko->id, $dari, $sampai),
            'metode' => $this->perMetode($toko->id, $dari, $sampai),
        ];
    }

    /**
     * Lima produk terlaris menurut JUMLAH, bukan omzet.
     *
     * Yang dicari pemilik toko di sini adalah "apa yang paling sering keluar",
     * karena itu yang menentukan belanja besok. Urutan menurut omzet akan
     * selalu dipimpin barang termahal yang terjual tiga kali.
     *
     * @return list<array{nama: string, jumlah: int, omzet: int}>
     */
    private function terlaris(int $tokoId, CarbonImmutable $dari, CarbonImmutable $sampai): array
    {
        return array_values(DB::table('transaksi_baris')
            ->join('transaksi', 'transaksi.id', '=', 'transaksi_baris.transaksi_id')
            ->where('transaksi.pos_user_id', $tokoId)
            ->where('transaksi.status', StatusTransaksi::Selesai->value)
            ->whereBetween('transaksi.waktu', [$dari, $sampai])
            // Dikelompokkan menurut NAMA, bukan produk_id: produk yang sudah
            // dihapus tetap punya sejarah penjualan, dan barisnya kehilangan
            // produk_id saat itu terjadi.
            ->groupBy('transaksi_baris.nama')
            ->select('transaksi_baris.nama')
            ->selectRaw('SUM(transaksi_baris.jumlah) as jumlah')
            ->selectRaw('SUM(transaksi_baris.harga_satuan * transaksi_baris.jumlah) as omzet')
            ->orderByDesc('jumlah')
            ->orderBy('transaksi_baris.nama')
            ->limit(5)
            ->get()
            ->map(static fn (object $b): array => [
                'nama' => (string) $b->nama,
                'jumlah' => (int) $b->jumlah,
                'omzet' => (int) $b->omzet,
            ])
            ->all());
    }

    /**
     * Porsi tiap metode pembayaran. Metode tanpa transaksi tidak ikut dikirim —
     * baris bernilai nol di grafik donat hanya menambah legenda tanpa isi.
     *
     * @return list<array{metode: string, metodeLabel: string, omzet: int, transaksi: int}>
     */
    private function perMetode(int $tokoId, CarbonImmutable $dari, CarbonImmutable $sampai): array
    {
        $rows = DB::table('transaksi')
            ->leftJoin('transaksi_baris', 'transaksi_baris.transaksi_id', '=', 'transaksi.id')
            ->where('transaksi.pos_user_id', $tokoId)
            ->where('transaksi.status', StatusTransaksi::Selesai->value)
            ->whereBetween('transaksi.waktu', [$dari, $sampai])
            ->groupBy('transaksi.metode')
            ->select('transaksi.metode')
            ->selectRaw('COALESCE(SUM(transaksi_baris.harga_satuan * transaksi_baris.jumlah), 0) as omzet')
            ->selectRaw('COUNT(DISTINCT transaksi.id) as transaksi')
            ->get()
            ->keyBy('metode');

        $hasil = [];
        foreach (MetodeBayarPos::cases() as $metodeEnum) {
            $row = $rows->get($metodeEnum->value);
            $hasil[] = [
                'metode' => $metodeEnum->value,
                'metodeLabel' => $metodeEnum->label(),
                'omzet' => $row ? (int) $row->omzet : 0,
                'transaksi' => $row ? (int) $row->transaksi : 0,
            ];
        }

        return $hasil;
    }
}
