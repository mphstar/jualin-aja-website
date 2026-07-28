<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\GerbangPembayaran;
use App\Enums\DurasiPaket;
use App\Enums\SaluranBayar;
use App\Enums\StatusPembayaran;
use App\Exceptions\KesalahanDomain;
use App\Models\Pembayaran;
use App\Models\PosUser;
use App\Support\HargaPaket;
use App\Support\KondisiLangganan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Terbitkan tagihan perpanjangan langganan dan minta instruksi bayarnya ke
 * Midtrans.
 *
 * Urutannya sengaja: **baris invoice disimpan LEBIH DULU**, baru gerbangnya
 * dipanggil. Kalau dibalik, transaksi yang sudah terbentuk di Midtrans bisa
 * gagal tercatat di sini — dan uang yang masuk untuk invoice yang tidak
 * pernah ada adalah keadaan yang tidak bisa diperbaiki sendiri oleh webhook,
 * karena webhook mencari barisnya lewat `order_id`.
 *
 * Panggilan gerbangnya di LUAR transaksi basis data. Menahan transaksi selama
 * panggilan HTTP berarti menahan kunci baris selama beberapa detik setiap kali
 * jaringan lambat — dan pada gerbang yang tidak menjawab, sampai timeout.
 */
final readonly class BuatTagihanLangganan
{
    public function __construct(private GerbangPembayaran $gerbang) {}

    public function __invoke(PosUser $toko, DurasiPaket $durasi, SaluranBayar $saluran): Pembayaran
    {
        if ($durasi === DurasiPaket::Trial) {
            throw new KesalahanDomain('Uji coba tidak dijual.');
        }

        $nominal = HargaPaket::untuk($durasi);

        if ($nominal <= 0) {
            throw new KesalahanDomain('Harga paket belum disetel. Hubungi dukungan.');
        }

        $pembayaran = DB::transaction(function () use ($toko, $durasi, $saluran, $nominal): Pembayaran {
            $sekarang = CarbonImmutable::now();

            /*
             * `order_id` harus unik SELAMANYA di sisi Midtrans, termasuk untuk
             * percobaan ulang atas paket yang sama. Nomor invoice saja tidak
             * cukup kalau nanti ada pembatalan lalu pembuatan ulang, jadi ia
             * diberi akhiran acak — sementara nomor invoice tetap rapi untuk
             * dibaca manusia di panel admin.
             */
            $nomorInvoice = (new NomorInvoiceBerikutnya)($sekarang);

            return Pembayaran::query()->create([
                'nomor_invoice' => $nomorInvoice,
                'pos_user_id' => $toko->id,
                'nominal' => $nominal,
                'durasi' => $durasi,
                'metode' => $saluran->metode(),
                'saluran' => $saluran,
                'status' => StatusPembayaran::Menunggu,
                'tanggal' => $sekarang,
                'berlaku_sampai' => KondisiLangganan::tanggalBerakhirBaru(
                    $toko->langganan_berakhir_pada,
                    $durasi,
                    $sekarang,
                ),
                'midtrans_order_id' => $nomorInvoice.'-'.Str::lower(Str::random(6)),
                'catatan' => 'Dibuat dari aplikasi POS.',
            ]);
        }, attempts: 3);

        try {
            $hasil = $this->gerbang->buatTransaksi($pembayaran, $saluran);
        } catch (Throwable $e) {
            /*
             * Barisnya TIDAK dihapus, hanya ditandai gagal. Dua alasan:
             *
             * 1. Kalau permintaannya sempat sampai dan hanya jawabannya yang
             *    hilang, Midtrans sudah punya transaksi dengan `order_id` ini —
             *    dan webhook-nya nanti mencari barisnya lewat kolom itu. Baris
             *    yang sudah dihapus membuat uang yang masuk tidak punya tempat
             *    untuk dicatat.
             * 2. Percobaan yang gagal adalah kejadian yang layak terlihat di
             *    panel admin, bukan sesuatu yang dihapus diam-diam.
             *
             * Yang tidak boleh tertinggal cuma status "Menunggu": tagihan itu
             * akan terus muncul di aplikasi sebagai sesuatu yang bisa dibayar,
             * padahal tidak ada instruksi bayarnya sama sekali.
             */
            $pembayaran->update([
                'status' => StatusPembayaran::Gagal,
                'catatan' => 'Gagal dibuat di gerbang pembayaran: '.$e->getMessage(),
            ]);

            throw $e;
        }

        $pembayaran->update([
            'midtrans_transaction_id' => $hasil->transactionId,
            'midtrans_payload' => $hasil->payload,
            'batas_bayar' => $hasil->batasBayar,
            'kode_bayar' => $hasil->kodeBayar,
            'kode_perusahaan' => $hasil->kodePerusahaan,
            'qr_url' => $hasil->qrUrl,
            'tautan_bayar' => $hasil->tautanBayar,
        ]);

        return $pembayaran->refresh();
    }
}
