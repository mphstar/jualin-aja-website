<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\JenisAksi;
use App\Enums\StatusEbook;
use App\Enums\StatusPembayaran;
use App\Enums\SumberLangganan;
use App\Enums\TargetAksi;
use App\Models\Ebook;
use App\Models\Langganan;
use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Models\PosUser;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Jejak audit yang DITURUNKAN dari data yang benar-benar ada.
 *
 * Entri log tidak dibangkitkan acak: tiap baris menunjuk perpanjangan,
 * penangguhan, penerbitan, atau pelunasan yang sungguh tercatat di tabel lain.
 * Log yang menyebut peristiwa fiktif akan langsung ketahuan begitu seseorang
 * mengklik entrinya dan tidak menemukan objeknya (PRD §F7.4).
 */
class LogAktivitasSeeder extends Seeder
{
    public function run(): void
    {
        $acak = new Acak(20260727);
        $sekarang = CarbonImmutable::now();
        $admin = User::query()->firstOrFail();

        $baris = [];

        $catat = function (
            JenisAksi $aksi,
            TargetAksi $targetTipe,
            string $deskripsi,
            int $hariLalu,
            ?string $targetId = null,
            ?string $targetLabel = null,
        ) use (&$baris, $acak, $sekarang, $admin): void {
            $waktu = $sekarang->subDays($hariLalu)
                ->setTime($acak->bulat(7, 22), $acak->bulat(0, 59), $acak->bulat(0, 59));
            $waktu = $waktu->greaterThan($sekarang) ? $sekarang : $waktu;

            $baris[] = [
                'waktu' => $waktu,
                'aktor_id' => $admin->id,
                'aktor_nama' => $admin->name,
                'aksi' => $aksi->value,
                'target_tipe' => $targetTipe->value,
                'target_id' => $targetId,
                'target_label' => $targetLabel,
                'deskripsi' => $deskripsi,
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ];
        };

        $namaToko = PosUser::query()->pluck('nama_toko', 'id');

        foreach (Langganan::query()->where('sumber', SumberLangganan::PerpanjanganManual->value)->get() as $langganan) {
            $toko = $namaToko->get($langganan->pos_user_id);
            if ($toko === null) {
                continue;
            }

            $catat(
                JenisAksi::LanggananPerpanjang,
                TargetAksi::Langganan,
                sprintf('Memperpanjang langganan %s sebesar %d bulan.', $toko, $langganan->durasi->bulan()),
                $acak->bulat(1, 88),
                (string) $langganan->pos_user_id,
                $toko,
            );
        }

        foreach (PosUser::query()->where('ditangguhkan', true)->get() as $posUser) {
            $catat(
                JenisAksi::UserTangguhkan,
                TargetAksi::User,
                sprintf('Menangguhkan akun %s. Alasan: %s', $posUser->nama_toko, $posUser->alasan_penangguhan),
                $acak->bulat(1, 60),
                (string) $posUser->id,
                $posUser->nama_toko,
            );
        }

        foreach (Ebook::query()->get() as $ebook) {
            $terbit = $ebook->status === StatusEbook::Terbit;

            $catat(
                $terbit ? JenisAksi::EbookTerbitkan : JenisAksi::EbookTambah,
                TargetAksi::Ebook,
                $terbit
                    ? sprintf('Menerbitkan ebook "%s".', $ebook->judul)
                    : sprintf('Menambahkan draf ebook "%s".', $ebook->judul),
                $acak->bulat(1, $terbit ? 90 : 30),
                (string) $ebook->id,
                $ebook->judul,
            );
        }

        $lunas = Pembayaran::query()
            ->where('status', StatusPembayaran::Lunas->value)
            ->inRandomOrder()
            ->limit(14)
            ->get();

        foreach ($lunas as $pembayaran) {
            $catat(
                JenisAksi::PembayaranLunas,
                TargetAksi::Pembayaran,
                sprintf('Menandai invoice %s sebagai lunas.', $pembayaran->nomor_invoice),
                $acak->bulat(1, 80),
                (string) $pembayaran->id,
                $pembayaran->nomor_invoice,
            );
        }

        for ($i = 0; $i < 10; $i++) {
            $catat(JenisAksi::Masuk, TargetAksi::Sistem, 'Masuk ke panel admin.', $acak->bulat(0, 40));
        }

        foreach (array_chunk($baris, 200) as $potongan) {
            LogAktivitas::query()->insert($potongan);
        }
    }
}
