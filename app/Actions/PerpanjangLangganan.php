<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DurasiPaket;
use App\Enums\JenisAksi;
use App\Enums\SumberLangganan;
use App\Enums\TargetAksi;
use App\Exceptions\KesalahanDomain;
use App\Models\Langganan;
use App\Models\PosUser;
use App\Services\PencatatAktivitas;
use App\Support\Format;
use App\Support\KondisiLangganan;
use App\Support\NamaAktor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Perpanjang langganan sebuah toko (PRD §F4.3–F4.5).
 *
 * SELALU membuat baris baru — riwayat lama tidak pernah ditimpa, supaya jejak
 * perpanjangan tetap bisa ditelusuri. Aturan tanggal berakhirnya tidak ditulis
 * di sini melainkan di KondisiLangganan, agar pelunasan invoice dan
 * perpanjangan manual tidak bisa berbeda hasil.
 */
final readonly class PerpanjangLangganan
{
    public function __construct(private PencatatAktivitas $pencatat) {}

    public function __invoke(
        PosUser $posUser,
        DurasiPaket $durasi,
        ?string $catatan = null,
        SumberLangganan $sumber = SumberLangganan::PerpanjanganManual,
    ): Langganan {
        if ($durasi === DurasiPaket::Trial) {
            throw new KesalahanDomain('Uji coba tidak bisa diperpanjang manual.');
        }

        return DB::transaction(function () use ($posUser, $durasi, $catatan, $sumber): Langganan {
            $sekarang = now();
            $berakhirSaatIni = $posUser->langganan_berakhir_pada;

            $akhirBaru = KondisiLangganan::tanggalBerakhirBaru($berakhirSaatIni, $durasi, $sekarang);

            // Siklus baru menyambung dari akhir siklus lama bila masih berjalan;
            // kalau sudah lewat, ia mulai hari ini.
            $mulaiBaru = $berakhirSaatIni !== null && $berakhirSaatIni->greaterThan($sekarang)
                ? $berakhirSaatIni
                : $sekarang;

            $baru = Langganan::query()->create([
                'pos_user_id' => $posUser->id,
                'durasi' => $durasi,
                'sumber' => $sumber,
                'tanggal_mulai' => $mulaiBaru,
                'tanggal_berakhir' => $akhirBaru,
                'dibuat_oleh' => NamaAktor::dari(Auth::user()),
                'catatan' => $catatan,
            ]);

            // Kolom ringkasan disegarkan oleh LanggananObserver; muat ulang
            // supaya pemanggil melihat kondisi terbaru, bukan yang sebelum simpan.
            $posUser->refresh();

            $this->pencatat->catat(
                aksi: JenisAksi::LanggananPerpanjang,
                targetTipe: TargetAksi::Langganan,
                deskripsi: sprintf(
                    'Memperpanjang langganan %s (%s) hingga %s.',
                    $posUser->nama_toko,
                    $durasi->label(),
                    Format::tanggal($akhirBaru),
                ),
                targetId: (string) $posUser->id,
                targetLabel: $posUser->nama_toko,
            );

            return $baru;
        });
    }
}
