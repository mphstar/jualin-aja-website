<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\StatusTransaksi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Pos\LanggananTokoResource;
use App\Http\Resources\Pos\TransaksiResource;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Support\OmzetHarian;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Ringkasan layar Beranda aplikasi POS.
 *
 * Digabung jadi satu permintaan, bukan lima. Layar itu punya SATU keadaan
 * memuat; lima permintaan yang selesai bergantian membuat halaman berkedut
 * empat kali sebelum tenang.
 */
class BerandaController extends Controller
{
    use MilikToko;

    /** @return array<string, mixed> */
    public function __invoke(Request $request): array
    {
        $toko = $this->toko($request);
        $toko->load('langgananBerlaku');

        $hariIni = CarbonImmutable::now()->startOfDay();
        $deret = OmzetHarian::deret($toko, $hariIni->subDays(6), $hariIni);

        $statistikHariIni = OmzetHarian::rentang($toko, $hariIni, $hariIni->endOfDay());
        $kemarin = OmzetHarian::rentang($toko, $hariIni->subDay(), $hariIni->subSecond());

        $piutang = Transaksi::query()
            ->milik($toko)
            ->where('status', StatusTransaksi::Ditahan->value)
            ->with('baris')
            ->get();

        return [
            'omzet' => $statistikHariIni['omzet'],
            'omzetKemarin' => $kemarin['omzet'],
            'transaksi' => $statistikHariIni['transaksi'],
            'item' => $statistikHariIni['item'],
            'tujuhHari' => array_map(static fn (array $t): int => $t['omzet'], $deret),
            'terakhir' => TransaksiResource::collection(
                Transaksi::query()
                    ->milik($toko)
                    ->with('baris')
                    ->orderByDesc('waktu')
                    ->orderByDesc('id')
                    ->limit(4)
                    ->get(),
            ),
            'langganan' => new LanggananTokoResource($toko),
            'produkHabis' => Produk::query()->milik($toko)
                ->where('lacak_stok', true)->where('stok', '<=', 0)->count(),
            'produkMenipis' => Produk::query()->milik($toko)
                ->where('lacak_stok', true)
                ->where('stok', '>', 0)
                ->where('stok', '<=', Produk::AMBANG_MENIPIS)
                ->count(),
            'piutangJumlah' => $piutang->count(),
            'piutangTotal' => $piutang->sum(fn (Transaksi $t): int => $t->total()),
        ];
    }
}
