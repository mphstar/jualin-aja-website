<?php

declare(strict_types=1);

namespace App\Actions\Pos;

use App\Enums\MetodeBayarPos;
use App\Enums\StatusTransaksi;
use App\Exceptions\KesalahanDomain;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;

/**
 * Terima pembayaran sebuah piutang.
 *
 * `waktu` sengaja TIDAK diubah — omzet tetap tercatat di hari barangnya
 * keluar, bukan di hari uangnya masuk. Memindahkannya akan membuat laporan
 * hari kemarin berubah setelah ditutup, dan laporan yang berubah sendiri
 * berhenti bisa dipakai untuk apa pun.
 *
 * Stok tidak disentuh: barangnya sudah keluar sejak piutangnya dibuat.
 */
final readonly class LunasiTransaksiPos
{
    public function __invoke(
        Transaksi $transaksi,
        MetodeBayarPos $metode,
        ?int $uangDiterima = null,
    ): Transaksi {
        return DB::transaction(function () use ($transaksi, $metode, $uangDiterima): Transaksi {
            /*
             * Dibaca ulang dengan kunci, bukan dipercaya dari model yang sudah
             * di tangan. Dua perangkat yang membuka piutang yang sama akan
             * sama-sama melihatnya "ditahan"; yang kedua harus ditolak, bukan
             * menimpa pelunasan yang pertama.
             */
            $terkunci = Transaksi::query()
                ->whereKey($transaksi->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($terkunci->status !== StatusTransaksi::Ditahan) {
                throw new KesalahanDomain('Transaksi ini bukan piutang yang menunggu pelunasan.');
            }

            $terkunci->load('baris');
            $total = $terkunci->total();

            if ($metode === MetodeBayarPos::Tunai && ($uangDiterima === null || $uangDiterima < $total)) {
                throw new KesalahanDomain('Uang yang diterima belum menutup sisa utang.');
            }

            $terkunci->update([
                'status' => StatusTransaksi::Selesai,
                'metode' => $metode,
                'uang_diterima' => $metode === MetodeBayarPos::Tunai ? $uangDiterima : null,
            ]);

            return $terkunci->load('baris');
        }, attempts: 3);
    }
}
