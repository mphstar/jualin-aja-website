<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\DurasiPaket;
use App\Enums\StatusLangganan;
use App\Enums\StatusPembayaran;
use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\PosUser;
use App\Support\FilterStatusLangganan;
use App\Support\Format;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class StatistikController extends Controller
{
    /** Panjang deret grafik dasbor (PRD §F2.2–F2.3). */
    private const int BULAN_DERET = 12;

    /** @return array<string, int|float> */
    public function dasbor(): array
    {
        $sekarang = CarbonImmutable::now();
        $awalBulanIni = $sekarang->startOfMonth();
        $awalBulanLalu = $awalBulanIni->subMonth();

        $totalUser = PosUser::query()->count();
        $userSebelumBulanIni = PosUser::query()->where('tanggal_daftar', '<', $awalBulanIni)->count();

        $langgananAktif = $this->jumlahBerstatus(StatusLangganan::Aktif)
            + $this->jumlahBerstatus(StatusLangganan::Trial);

        return [
            'totalUser' => $totalUser,
            'deltaUserPersen' => $this->delta($totalUser, $userSebelumBulanIni),
            'langgananAktif' => $langgananAktif,
            'deltaAktifPersen' => $this->delta($langgananAktif, $this->aktifPada($awalBulanIni)),
            'akanBerakhir' => $this->jumlahBerstatus(StatusLangganan::AkanBerakhir),
            'kedaluwarsa' => $this->jumlahBerstatus(StatusLangganan::Kedaluwarsa),
            'pendapatanBulanIni' => $this->pendapatan($awalBulanIni, null),
            'deltaPendapatanPersen' => $this->delta(
                $this->pendapatan($awalBulanIni, null),
                $this->pendapatan($awalBulanLalu, $awalBulanIni),
            ),
        ];
    }

    /**
     * Pendaftaran per bulan, 12 bulan terakhir (PRD §F2.2).
     *
     * @return list<array{label: string, nilai: int}>
     */
    public function trenPendaftaran(): array
    {
        return $this->deretBulanan(function (CarbonImmutable $dari, CarbonImmutable $sampai): int {
            return PosUser::query()
                ->where('tanggal_daftar', '>=', $dari)
                ->where('tanggal_daftar', '<', $sampai)
                ->count();
        });
    }

    /**
     * Pendapatan lunas per bulan, 12 bulan terakhir (PRD §F2.3).
     *
     * @return list<array{label: string, nilai: int}>
     */
    public function trenPendapatan(): array
    {
        return $this->deretBulanan(fn (CarbonImmutable $dari, CarbonImmutable $sampai): int => $this->pendapatan($dari, $sampai));
    }

    /**
     * Komposisi durasi paket yang sedang dipakai tiap toko (PRD §F2.4).
     *
     * @return list<array{durasi: string, jumlah: int}>
     */
    public function komposisiPaket(): array
    {
        $jumlah = PosUser::query()
            ->whereNotNull('langganan_durasi')
            ->selectRaw('langganan_durasi, count(*) as jumlah')
            ->groupBy('langganan_durasi')
            ->pluck('jumlah', 'langganan_durasi');

        // Urutan tetap TRIAL → BULANAN → SEMESTERAN → TAHUNAN supaya potongan
        // donat tidak berpindah warna saat komposisinya berubah.
        return array_map(
            static fn (DurasiPaket $durasi): array => [
                'durasi' => $durasi->value,
                'jumlah' => (int) $jumlah->get($durasi->value, 0),
            ],
            DurasiPaket::cases(),
        );
    }

    private function jumlahBerstatus(StatusLangganan $status): int
    {
        $query = PosUser::query();
        FilterStatusLangganan::terapkan(
            $query, $status,
            'langganan_berakhir_pada', 'langganan_sumber', 'ditangguhkan',
        );

        return $query->count();
    }

    /**
     * Berapa toko yang langganannya sedang berjalan pada satu titik waktu.
     *
     * Dihitung dari riwayat langganan, bukan dari kolom ringkasan: kolom itu
     * hanya tahu keadaan SEKARANG, sementara pembanding bulan lalu menuntut
     * keadaan pada saat itu.
     */
    private function aktifPada(CarbonImmutable $waktu): int
    {
        return PosUser::query()
            ->where('ditangguhkan', false)
            ->whereHas('langganan', function (EloquentBuilder $q) use ($waktu): void {
                $q->where('tanggal_mulai', '<=', $waktu)
                    ->where('tanggal_berakhir', '>', $waktu);
            })
            ->count();
    }

    private function pendapatan(CarbonImmutable $dari, ?CarbonImmutable $sampai): int
    {
        return (int) Pembayaran::query()
            ->where('status', StatusPembayaran::Lunas->value)
            ->where('tanggal', '>=', $dari)
            ->when($sampai !== null, fn (EloquentBuilder $q): EloquentBuilder => $q->where('tanggal', '<', $sampai))
            ->sum('nominal');
    }

    /**
     * @param  callable(CarbonImmutable, CarbonImmutable): int  $hitung
     * @return list<array{label: string, nilai: int}>
     */
    private function deretBulanan(callable $hitung): array
    {
        $awal = CarbonImmutable::now()->startOfMonth();
        $deret = [];

        for ($i = self::BULAN_DERET - 1; $i >= 0; $i--) {
            $bulan = $awal->subMonths($i);
            $deret[] = [
                'label' => Format::bulanSingkat($bulan),
                'nilai' => $hitung($bulan, $bulan->addMonth()),
            ];
        }

        return $deret;
    }

    /** Perubahan dalam persen; pembagi nol diperlakukan sebagai 0% atau 100%. */
    private function delta(int $sekarang, int $sebelumnya): float
    {
        if ($sebelumnya === 0) {
            return $sekarang > 0 ? 100.0 : 0.0;
        }

        return (($sekarang - $sebelumnya) / $sebelumnya) * 100;
    }
}
