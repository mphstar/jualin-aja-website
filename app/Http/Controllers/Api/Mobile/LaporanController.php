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
        $periode = PeriodeLaporan::tryFrom((string) $request->string('periode'))
            ?? PeriodeLaporan::HariIni;

        $sampai = CarbonImmutable::now()->endOfDay();
        $dari = $sampai->startOfDay()->subDays($periode->hari() - 1);

        $ringkas = OmzetHarian::rentang($toko, $dari, $sampai);

        return [
            'periode' => $periode->value,
            'periodeLabel' => $periode->label(),
            'dari' => $dari->toISOString(),
            'sampai' => $sampai->toISOString(),
            ...$ringkas,
            'rataPerStruk' => $ringkas['transaksi'] === 0
                ? 0
                : intdiv($ringkas['omzet'], $ringkas['transaksi']),
            'harian' => OmzetHarian::deret($toko, $dari, $sampai),
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
        return array_values(DB::table('transaksi')
            ->leftJoin('transaksi_baris', 'transaksi_baris.transaksi_id', '=', 'transaksi.id')
            ->where('transaksi.pos_user_id', $tokoId)
            ->where('transaksi.status', StatusTransaksi::Selesai->value)
            ->whereBetween('transaksi.waktu', [$dari, $sampai])
            ->groupBy('transaksi.metode')
            ->select('transaksi.metode')
            ->selectRaw('COALESCE(SUM(transaksi_baris.harga_satuan * transaksi_baris.jumlah), 0) as omzet')
            ->selectRaw('COUNT(DISTINCT transaksi.id) as transaksi')
            ->orderByDesc('omzet')
            ->get()
            ->map(static fn (object $b): array => [
                'metode' => (string) $b->metode,
                'metodeLabel' => MetodeBayarPos::from((string) $b->metode)->label(),
                'omzet' => (int) $b->omzet,
                'transaksi' => (int) $b->transaksi,
            ])
            ->all());
    }
}
