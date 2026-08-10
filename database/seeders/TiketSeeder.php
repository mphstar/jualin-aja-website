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
        $pengguna = PosUser::query()->first();
        $admin = User::query()->first();

        if (! $pengguna) {
            return;
        }

        TiketDukungan::query()->create([
            'pos_user_id' => $pengguna->id,
            'nomor_tiket' => 'TKT-20260810-0001',
            'jenis' => JenisTiket::Saran->value,
            'subjek' => 'Saran Fitur Cetak Laporan Shift Kasir',
            'pesan' => 'Halo min, mohon agar di aplikasi kasir mobile ditambahkan fitur untuk mencetak ringkasan penutupan sesi kasir ke printer thermal Bluetooth. Terima kasih!',
            'status' => StatusTiket::Diproses->value,
            'prioritas' => PrioritasTiket::Sedang->value,
            'balasan_admin' => 'Halo kak! Terima kasih atas sarannya. Fitur cetak laporan shift saat ini sudah masuk dalam roadmap pengembangan versi berikutnya.',
            'dibalas_pada' => now(),
            'dibalas_oleh_id' => $admin?->id,
        ]);

        TiketDukungan::query()->create([
            'pos_user_id' => $pengguna->id,
            'nomor_tiket' => 'TKT-20260810-0002',
            'jenis' => JenisTiket::Komplain->value,
            'subjek' => 'Stok produk tidak berkurang otomatis',
            'pesan' => 'Saya sempat menemukan kendala di mana setelah transaksi simpan bayar tunai, stok beberapa produk tidak langsung ter-update di layar daftar produk.',
            'status' => StatusTiket::Terbuka->value,
            'prioritas' => PrioritasTiket::Tinggi->value,
        ]);
    }
}
