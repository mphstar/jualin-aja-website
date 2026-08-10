<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\JenisTiket;
use App\Enums\PrioritasTiket;
use App\Enums\StatusTiket;
use App\Models\PosUser;
use App\Models\TiketDukungan;
use App\Models\User;
use Illuminate\Database\Seeder;

class TiketSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->first();
        $semuaPengguna = PosUser::query()->get();

        if ($semuaPengguna->isEmpty()) {
            return;
        }

        $i = 1;
        foreach ($semuaPengguna as $p) {
            $nomor1 = sprintf('TKT-%s-%04d', now()->format('Ymd'), $i++);
            TiketDukungan::query()->firstOrCreate(
                ['nomor_tiket' => $nomor1],
                [
                    'pos_user_id' => $p->id,
                    'jenis' => JenisTiket::Saran->value,
                    'subjek' => 'Saran Fitur Cetak Laporan Shift Kasir',
                    'pesan' => 'Halo min, mohon agar di aplikasi kasir mobile ditambahkan fitur untuk mencetak ringkasan penutupan sesi kasir ke printer thermal Bluetooth. Terima kasih!',
                    'status' => StatusTiket::Diproses->value,
                    'prioritas' => PrioritasTiket::Sedang->value,
                    'balasan_admin' => 'Halo kak! Terima kasih atas sarannya. Fitur cetak laporan shift saat ini sudah masuk dalam roadmap pengembangan versi berikutnya.',
                    'dibalas_pada' => now(),
                    'dibalas_oleh_id' => $admin?->id,
                ]
            );

            $nomor2 = sprintf('TKT-%s-%04d', now()->format('Ymd'), $i++);
            TiketDukungan::query()->firstOrCreate(
                ['nomor_tiket' => $nomor2],
                [
                    'pos_user_id' => $p->id,
                    'jenis' => JenisTiket::Komplain->value,
                    'subjek' => 'Kendala pembaruan stok produk',
                    'pesan' => 'Saya sempat menemukan kendala di mana setelah transaksi simpan bayar tunai, stok beberapa produk butuh waktu beberapa detik untuk ter-update.',
                    'status' => StatusTiket::Terbuka->value,
                    'prioritas' => PrioritasTiket::Tinggi->value,
                ]
            );
        }
    }
}
