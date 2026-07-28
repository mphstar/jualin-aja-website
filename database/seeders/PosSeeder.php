<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DurasiPaket;
use App\Enums\IkonKategori;
use App\Enums\JenisUsaha;
use App\Enums\MetodeBayarPos;
use App\Enums\StatusTransaksi;
use App\Enums\SumberLangganan;
use App\Models\BarisTransaksi;
use App\Models\Kategori;
use App\Models\Langganan;
use App\Models\PengaturanStruk;
use App\Models\PosUser;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Support\KondisiLangganan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Isi kasir: kategori, produk, dan riwayat penjualan.
 *
 * Dijalankan SETELAH PenggunaLanggananSeeder karena menempel pada toko yang
 * sudah ada. Hanya sebagian toko yang diisi — toko yang baru mendaftar dan
 * belum menyentuh kasirnya adalah keadaan yang juga harus terlihat di panel
 * admin, dan kalau semuanya diisi, keadaan kosong tidak akan pernah teruji.
 *
 * Stok TIDAK dikurangi di sini walau transaksinya nyata: seeder menulis
 * langsung ke tabel, bukan lewat SimpanTransaksiPos. Angka stok diundi
 * belakangan supaya sebarannya (habis / menipis / aman) memang terlihat di
 * Beranda aplikasi, bukan kebetulan hasil pengurangan.
 */
class PosSeeder extends Seeder
{
    /** Toko demo yang dipakai aplikasi mobile untuk masuk. */
    public const string EMAIL_DEMO = 'bintang@kopisenja.id';

    public const string SANDI_DEMO = 'kopisenja';

    /** Berapa toko yang diberi isi kasir. Sisanya sengaja dibiarkan kosong. */
    private const int JUMLAH_TOKO_BERISI = 12;

    /** Berapa hari ke belakang riwayat penjualan dibangkitkan. */
    private const int HARI_RIWAYAT = 30;

    /** @var array<string, list<array{0: string, 1: int, 2: string}>> */
    private const array MENU = [
        'Kopi' => [
            ['Kopi Susu Gula Aren', 22_000, 'gelas'],
            ['Americano', 18_000, 'gelas'],
            ['Cappuccino', 25_000, 'gelas'],
            ['Kopi Tubruk', 12_000, 'gelas'],
        ],
        'Non-Kopi' => [
            ['Matcha Latte', 26_000, 'gelas'],
            ['Cokelat Panas', 20_000, 'gelas'],
            ['Es Teh Manis', 8_000, 'gelas'],
        ],
        'Makanan' => [
            ['Nasi Goreng Kampung', 28_000, 'porsi'],
            ['Mie Ayam Bakso', 25_000, 'porsi'],
            ['Ayam Geprek Sambal Matah', 30_000, 'porsi'],
        ],
        'Camilan' => [
            ['Pisang Goreng Keju', 18_000, 'porsi'],
            ['Kentang Goreng', 20_000, 'porsi'],
            ['Roti Bakar Cokelat', 16_000, 'pcs'],
        ],
    ];

    /** @var array<string, IkonKategori> */
    private const array IKON = [
        'Kopi' => IkonKategori::Coffee,
        'Non-Kopi' => IkonKategori::LocalDrink,
        'Makanan' => IkonKategori::RiceBowl,
        'Camilan' => IkonKategori::Cookie,
    ];

    private Acak $acak;

    private CarbonImmutable $sekarang;

    public function run(): void
    {
        $this->acak = new Acak(20260728);
        $this->sekarang = CarbonImmutable::now();

        $demo = $this->buatTokoDemo();

        /*
         * Yang diisi adalah toko yang langganannya masih berjalan. Kasir penuh
         * transaksi pada toko yang statusnya kedaluwarsa akan terbaca aneh di
         * panel admin — seolah aplikasinya tidak pernah benar-benar mengunci.
         */
        $toko = PosUser::query()
            ->whereKeyNot($demo->id)
            ->where('ditangguhkan', false)
            ->where('langganan_berakhir_pada', '>=', $this->sekarang)
            ->orderBy('id')
            ->limit(self::JUMLAH_TOKO_BERISI)
            ->get();

        Model::withoutEvents(function () use ($demo, $toko): void {
            $this->isiToko($demo, riwayatPenuh: true);

            foreach ($toko as $satu) {
                $this->isiToko($satu, riwayatPenuh: false);
            }
        });
    }

    /**
     * Toko demo dengan kata sandi yang diketahui.
     *
     * Ditampilkan terang-terangan di layar masuk aplikasi karena ini memang
     * akun peragaan — menyembunyikannya seolah nyata justru membuat orang
     * mengira ada kredensial sungguhan yang bocor.
     */
    private function buatTokoDemo(): PosUser
    {
        $mulai = $this->sekarang->subMonths(7);

        $demo = PosUser::query()->create([
            'nama' => 'Bintang Pratama',
            'email' => self::EMAIL_DEMO,
            'password' => self::SANDI_DEMO,
            'telepon' => '0812-3456-7890',
            'nama_toko' => 'Kopi Senja',
            'jenis_usaha' => JenisUsaha::Kafe,
            'kota' => 'Bandung',
            'alamat' => 'Jl. Cihampelas No. 148, Bandung',
            'tanggal_daftar' => $mulai,
            'ditangguhkan' => false,
        ]);

        Langganan::query()->create([
            'pos_user_id' => $demo->id,
            'durasi' => DurasiPaket::Trial,
            'sumber' => SumberLangganan::Trial,
            'tanggal_mulai' => $mulai,
            'tanggal_berakhir' => $mulai->addDays(KondisiLangganan::LAMA_TRIAL_HARI),
            'catatan' => 'Uji coba otomatis saat pendaftaran.',
        ]);

        Langganan::query()->create([
            'pos_user_id' => $demo->id,
            'durasi' => DurasiPaket::Semesteran,
            'sumber' => SumberLangganan::Pembelian,
            'tanggal_mulai' => $mulai->addDays(KondisiLangganan::LAMA_TRIAL_HARI),
            'tanggal_berakhir' => $this->sekarang->addDays(23),
            'catatan' => 'Pembelian pertama.',
        ]);

        $demo->segarkanRingkasanLangganan();

        return $demo->refresh();
    }

    private function isiToko(PosUser $toko, bool $riwayatPenuh): void
    {
        PengaturanStruk::query()->firstOrCreate(
            ['pos_user_id' => $toko->id],
            PengaturanStruk::bawaan(),
        );

        if ($toko->alamat === null) {
            $toko->forceFill([
                'alamat' => 'Jl. '.$this->acak->pilih(KolamData::KOTA).' No. '.$this->acak->bulat(1, 199),
            ])->saveQuietly();
        }

        $produk = $this->isiKatalog($toko);
        $this->isiRiwayat($toko, $produk, $riwayatPenuh ? self::HARI_RIWAYAT : 7);
    }

    /** @return list<Produk> */
    private function isiKatalog(PosUser $toko): array
    {
        $produk = [];
        $urutan = 0;

        foreach (self::MENU as $namaKategori => $daftar) {
            $kategori = Kategori::query()->create([
                'pos_user_id' => $toko->id,
                'nama' => $namaKategori,
                'ikon' => self::IKON[$namaKategori],
                'urutan' => $urutan++,
            ]);

            foreach ($daftar as [$nama, $harga, $satuan]) {
                /*
                 * Sepertiga produk melacak stok, dan sebarannya diundi supaya
                 * ketiga keadaan — habis, menipis, aman — memang muncul di
                 * Beranda tanpa harus menjual apa pun lebih dulu.
                 */
                $lacak = $this->acak->peluang(0.35);

                $produk[] = Produk::query()->create([
                    'pos_user_id' => $toko->id,
                    'kategori_id' => $kategori->id,
                    'nama' => $nama,
                    'harga_jual' => $harga,
                    'satuan' => $satuan,
                    'lacak_stok' => $lacak,
                    'stok' => $lacak ? $this->stokAwal() : 0,
                ]);
            }
        }

        return $produk;
    }

    /**
     * Stok awal yang sengaja tersebar ke tiga keadaan.
     *
     * Habis, menipis, dan aman semuanya harus muncul di Beranda aplikasi tanpa
     * pemilik toko perlu menjual apa pun lebih dulu — kalau tidak, dua dari
     * tiga tampilan itu tidak pernah benar-benar terlihat saat ditinjau.
     */
    private function stokAwal(): int
    {
        return match ($this->acak->pilihBerbobot(['habis' => 1, 'menipis' => 2, 'aman' => 7])) {
            'habis' => 0,
            'menipis' => $this->acak->bulat(1, Produk::AMBANG_MENIPIS),
            default => $this->acak->bulat(12, 40),
        };
    }

    /** @param  list<Produk>  $produk */
    private function isiRiwayat(PosUser $toko, array $produk, int $hari): void
    {
        $nomor = 0;

        for ($mundur = $hari - 1; $mundur >= 0; $mundur--) {
            $tanggal = $this->sekarang->subDays($mundur)->startOfDay();

            // Akhir pekan lebih ramai — grafik tujuh hari yang rata membuat
            // seluruh halaman Laporan terlihat seperti data palsu.
            $ramai = $tanggal->isWeekend() ? 1.6 : 1.0;
            $jumlahStruk = (int) round($this->acak->bulat(4, 14) * $ramai);

            for ($s = 0; $s < $jumlahStruk; $s++) {
                $nomor++;
                $this->buatStruk($toko, $produk, $tanggal, $nomor);
            }
        }

        $toko->forceFill(['nomor_struk_terakhir' => $nomor])->saveQuietly();
    }

    /** @param  list<Produk>  $produk */
    private function buatStruk(PosUser $toko, array $produk, CarbonImmutable $tanggal, int $nomor): void
    {
        // Jam buka warung: 07.00–21.00. Struk tengah malam membuat grafik jam
        // sibuk nanti tidak berarti apa-apa.
        $waktu = $tanggal->addHours($this->acak->bulat(7, 21))->addMinutes($this->acak->bulat(0, 59));

        $status = $this->acak->pilihBerbobot([
            StatusTransaksi::Selesai->value => 92,
            StatusTransaksi::Ditahan->value => 8,
        ]);
        $piutang = $status === StatusTransaksi::Ditahan->value;

        $baris = [];
        $total = 0;

        foreach ($this->acak->kocok($produk) as $indeks => $p) {
            if ($indeks >= $this->acak->bulat(1, 4)) {
                break;
            }

            $jumlah = match ($this->acak->pilihBerbobot(['satu' => 6, 'dua' => 3, 'tiga' => 1])) {
                'satu' => 1,
                'dua' => 2,
                default => 3,
            };

            $baris[] = [
                'produk_id' => $p->id,
                'nama' => $p->nama,
                'harga_satuan' => $p->harga_jual,
                'jumlah' => $jumlah,
            ];
            $total += $p->harga_jual * $jumlah;
        }

        $metode = $piutang
            ? MetodeBayarPos::Tunai
            : MetodeBayarPos::from($this->acak->pilihBerbobot([
                'TUNAI' => 6, 'QRIS' => 3, 'TRANSFER' => 1,
            ]));

        $transaksi = Transaksi::query()->create([
            'pos_user_id' => $toko->id,
            'nomor_struk' => sprintf('STR/%s/%s', $waktu->year, str_pad((string) $nomor, 4, '0', STR_PAD_LEFT)),
            'waktu' => $waktu,
            'metode' => $metode,
            'status' => $piutang ? StatusTransaksi::Ditahan : StatusTransaksi::Selesai,
            'pelanggan' => $piutang
                ? $this->acak->pilih(KolamData::NAMA_DEPAN).' '.$this->acak->pilih(KolamData::NAMA_BELAKANG)
                : null,
            // Dibulatkan ke atas ke kelipatan 5.000 — itu yang benar-benar
            // diserahkan pembeli, bukan uang pas sampai rupiah terakhir.
            'uang_diterima' => ! $piutang && $metode === MetodeBayarPos::Tunai
                ? (int) (ceil($total / 5000) * 5000)
                : null,
        ]);

        foreach ($baris as $satu) {
            BarisTransaksi::query()->create([...$satu, 'transaksi_id' => $transaksi->id]);
        }
    }
}
