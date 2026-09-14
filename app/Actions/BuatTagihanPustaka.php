<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\GerbangPembayaran;
use App\Enums\DurasiPaket;
use App\Enums\SaluranBayar;
use App\Enums\StatusPembayaran;
use App\Exceptions\KesalahanDomain;
use App\Models\Ebook;
use App\Models\Pembayaran;
use App\Models\PosUser;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Terbitkan tagihan pembelian satuan konten Pustaka dan minta instrumen bayarnya ke Mayar.
 */
final readonly class BuatTagihanPustaka
{
    public function __construct(private GerbangPembayaran $gerbang) {}

    public function __invoke(PosUser $toko, Ebook $ebook, SaluranBayar $saluran): Pembayaran
    {
        if ($toko->punyaAksesEbook($ebook->id)) {
            throw new KesalahanDomain('Anda sudah memiliki akses ke pustaka ini.');
        }

        $nominal = $ebook->harga > 0 ? $ebook->harga : 25000;

        // Cek apakah ada tagihan MENUNGGU yang belum kadaluwarsa untuk ebook ini.
        $sekarang = CarbonImmutable::now();
        $yangAda = Pembayaran::query()
            ->where('pos_user_id', $toko->id)
            ->where('ebook_id', $ebook->id)
            ->where('status', StatusPembayaran::Menunggu)
            ->where('batas_bayar', '>', $sekarang)
            ->orderByDesc('id')
            ->first();

        if ($yangAda !== null) {
            return $yangAda;
        }

        $pembayaran = DB::transaction(function () use ($toko, $ebook, $saluran, $nominal): Pembayaran {
            $sekarang = CarbonImmutable::now();

            return Pembayaran::query()->create([
                'nomor_invoice' => (new NomorInvoiceBerikutnya)($sekarang),
                'pos_user_id' => $toko->id,
                'nominal' => $nominal,
                'durasi' => DurasiPaket::Bulanan, // default placeholder
                'tipe' => Pembayaran::TIPE_PUSTAKA_SATUAN,
                'ebook_id' => $ebook->id,
                'metode' => $saluran->grup(),
                'saluran' => $saluran->value,
                'status' => StatusPembayaran::Menunggu,
                'tanggal' => $sekarang,
                'catatan' => 'Pembelian satuan pustaka: '.$ebook->judul,
            ]);
        }, attempts: 3);

        try {
            $hasil = $this->gerbang->buatTransaksi($pembayaran, $saluran);
        } catch (Throwable $e) {
            $pembayaran->update([
                'status' => StatusPembayaran::Gagal,
                'catatan' => 'Gagal dibuat di gerbang pembayaran: '.$e->getMessage(),
            ]);

            throw $e;
        }

        $pembayaran->update([
            'mayar_order_id' => $hasil->orderId,
            'mayar_transaction_id' => $hasil->transactionId,
            'mayar_payload' => $hasil->payload,
            'batas_bayar' => $hasil->batasBayar,
            'kedaluwarsa_saluran' => $hasil->kedaluwarsaSaluran,
            'kode_bayar' => $hasil->kodeBayar,
            'kode_perusahaan' => $hasil->kodePerusahaan,
            'tautan_bayar' => $hasil->tautanBayar,
            'instruksi_bayar' => $hasil->instruksi,
        ]);

        return $pembayaran->refresh();
    }
}
