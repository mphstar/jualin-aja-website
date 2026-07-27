<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DurasiPaket;
use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Enums\SumberLangganan;
use App\Models\Langganan;
use App\Models\Pembayaran;
use App\Models\PosUser;
use App\Support\KondisiLangganan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * 48 pemilik toko beserta riwayat langganan dan pembayarannya.
 *
 * Ketiganya dibangkitkan bersama karena memang saling terikat: sebuah siklus
 * langganan selalu punya invoice, dan tanggal daftar toko diturunkan dari
 * siklus pertamanya. Memisahkannya jadi tiga seeder berarti membangun ulang
 * tanggal yang sama tiga kali dan berisiko tidak konsisten.
 */
class PenggunaLanggananSeeder extends Seeder
{
    /**
     * Sebaran status dibuat BY CONSTRUCTION, bukan diserahkan pada keberuntungan
     * (PRD §9.6) — dasbor harus menampilkan campuran yang layak ditinjau setiap
     * kali database dibangun ulang.
     *
     * @var array<string, int>
     */
    private const array SEBARAN = [
        'AKTIF' => 18,
        'AKAN_BERAKHIR' => 7,
        'KEDALUWARSA' => 9,
        'TRIAL' => 8,
        'NONAKTIF' => 6,
    ];

    /** @var array<string, int> */
    private const array HARGA = [
        'TRIAL' => 0,
        'BULANAN' => 99_000,
        'SEMESTERAN' => 499_000,
        'TAHUNAN' => 899_000,
    ];

    private Acak $acak;

    private CarbonImmutable $sekarang;

    private int $nomorInvoice = 1;

    public function run(): void
    {
        $this->acak = new Acak(20260725);
        $this->sekarang = CarbonImmutable::now();

        $antrian = [];
        foreach (self::SEBARAN as $status => $jumlah) {
            $antrian = [...$antrian, ...array_fill(0, $jumlah, $status)];
        }

        /*
         * Observer dimatikan selama pembangkitan lalu ringkasan disegarkan
         * sekali di akhir: 200-an siklus langganan berarti 200-an pembacaan
         * ulang yang hasil akhirnya sama saja.
         */
        Model::withoutEvents(function () use ($antrian): void {
            foreach ($this->acak->kocok($antrian) as $indeks => $target) {
                $this->buatToko($indeks, (string) $target);
            }
        });

        PosUser::query()->each(fn (PosUser $posUser) => $posUser->segarkanRingkasanLangganan());
    }

    private function buatToko(int $indeks, string $target): void
    {
        $nama = $this->acak->pilih(KolamData::NAMA_DEPAN).' '.$this->acak->pilih(KolamData::NAMA_BELAKANG);
        $namaToko = $this->acak->pilih(KolamData::AWALAN_TOKO).' '.$this->acak->pilih(KolamData::AKHIRAN_TOKO);

        if ($target === 'TRIAL') {
            $this->buatTokoTrial($indeks, $nama, $namaToko);

            return;
        }

        $this->buatTokoBerbayar($indeks, $nama, $namaToko, $target);
    }

    /** Toko yang masih di masa uji coba: satu siklus, belum pernah membayar. */
    private function buatTokoTrial(int $indeks, string $nama, string $namaToko): void
    {
        $akhir = $this->sekarang->addDays($this->acak->bulat(8, KondisiLangganan::LAMA_TRIAL_HARI));
        $mulai = $akhir->subDays(KondisiLangganan::LAMA_TRIAL_HARI);

        $posUser = $this->simpanPosUser($indeks, $nama, $namaToko, $mulai, ditangguhkan: false);
        $this->simpanTrial($posUser, $mulai, $akhir);
    }

    private function buatTokoBerbayar(int $indeks, string $nama, string $namaToko, string $target): void
    {
        $jumlahSiklus = $this->acak->bulat(1, 4);

        /** @var list<DurasiPaket> $durasiSiklus */
        $durasiSiklus = [];
        for ($s = 0; $s < $jumlahSiklus; $s++) {
            $durasiSiklus[] = DurasiPaket::from($this->acak->pilihBerbobot([
                'BULANAN' => 5,
                'SEMESTERAN' => 3,
                'TAHUNAN' => 2,
            ]));
        }

        /*
         * Sisa hari dibatasi panjang siklus TERAKHIR: paket 1 bulan tidak
         * mungkin menyisakan 300 hari. Tanpa batas ini siklus terakhir mulai di
         * masa depan, dan tanggal daftar — yang dihitung mundur dari situ —
         * ikut terlempar ke masa depan.
         */
        $sisaMaksimum = $durasiSiklus[$jumlahSiklus - 1]->bulan() * 30 - 2;
        $sisaHari = match ($target) {
            'AKTIF' => $this->acak->bulat(KondisiLangganan::AMBANG_AKAN_BERAKHIR + 1, $sisaMaksimum),
            'AKAN_BERAKHIR' => $this->acak->bulat(0, KondisiLangganan::AMBANG_AKAN_BERAKHIR),
            'KEDALUWARSA' => -$this->acak->bulat(1, 180),
            // NONAKTIF: tanggalnya bebas, statusnya di-override flag penangguhan.
            default => $this->acak->bulat(-90, $sisaMaksimum),
        };

        $akhirTerakhir = $this->sekarang->addDays($sisaHari);

        /*
         * Riwayat dibangun MUNDUR dari tanggal berakhir terakhir. Kalau dibangun
         * maju dari tanggal daftar, siklus di tengah bisa berakhir lebih jauh
         * daripada siklus terakhir — dan "langganan yang berlaku" (tanggal
         * berakhir terjauh) akan menunjuk siklus yang salah, sehingga status
         * yang tampil tidak sesuai target sebaran.
         */
        $batas = [$akhirTerakhir];
        for ($s = $jumlahSiklus - 1; $s >= 0; $s--) {
            array_unshift($batas, $batas[0]->subMonths($durasiSiklus[$s]->bulan()));
        }

        $mulaiTrial = $batas[0]->subDays(KondisiLangganan::LAMA_TRIAL_HARI);
        $posUser = $this->simpanPosUser($indeks, $nama, $namaToko, $mulaiTrial, $target === 'NONAKTIF');

        // Masa uji coba selalu mendahului siklus berbayar pertama.
        $this->simpanTrial($posUser, $mulaiTrial, $batas[0]);

        for ($s = 0; $s < $jumlahSiklus; $s++) {
            $this->simpanSiklusBerbayar($posUser, $durasiSiklus[$s], $batas[$s], $batas[$s + 1]);
        }
    }

    private function simpanPosUser(
        int $indeks,
        string $nama,
        string $namaToko,
        CarbonImmutable $tanggalDaftar,
        bool $ditangguhkan,
    ): PosUser {
        return PosUser::query()->create([
            'nama' => $nama,
            'email' => Str::of($nama)->lower()->replaceMatches('/\s+/', '.')->value().$indeks.'@gmail.com',
            'telepon' => '08'.$this->acak->bulat(11, 89).$this->acak->bulat(10_000_000, 99_999_999),
            'nama_toko' => $namaToko,
            'jenis_usaha' => $this->acak->pilih(KolamData::jenisUsaha()),
            'kota' => $this->acak->pilih(KolamData::KOTA),
            'tanggal_daftar' => $tanggalDaftar,
            'ditangguhkan' => $ditangguhkan,
            'alasan_penangguhan' => $ditangguhkan
                ? $this->acak->pilih(KolamData::ALASAN_PENANGGUHAN)
                : null,
        ]);
    }

    private function simpanTrial(PosUser $posUser, CarbonImmutable $mulai, CarbonImmutable $akhir): void
    {
        Langganan::query()->create([
            'pos_user_id' => $posUser->id,
            'durasi' => DurasiPaket::Trial,
            'sumber' => SumberLangganan::Trial,
            'tanggal_mulai' => $mulai,
            'tanggal_berakhir' => $akhir,
            'catatan' => 'Uji coba otomatis saat pendaftaran.',
        ]);
    }

    private function simpanSiklusBerbayar(
        PosUser $posUser,
        DurasiPaket $durasi,
        CarbonImmutable $mulai,
        CarbonImmutable $akhir,
    ): void {
        $manual = $this->acak->peluang(0.15);

        $langganan = Langganan::query()->create([
            'pos_user_id' => $posUser->id,
            'durasi' => $durasi,
            'sumber' => $manual ? SumberLangganan::PerpanjanganManual : SumberLangganan::Pembelian,
            'tanggal_mulai' => $mulai,
            'tanggal_berakhir' => $akhir,
            'dibuat_oleh' => $manual ? DatabaseSeeder::NAMA_ADMIN : null,
            'catatan' => $manual ? 'Diperpanjang manual oleh admin.' : null,
        ]);

        $status = StatusPembayaran::from($this->acak->pilihBerbobot([
            'LUNAS' => 85,
            'MENUNGGU' => 8,
            'GAGAL' => 5,
            'REFUND' => 2,
        ]));

        Pembayaran::query()->create([
            'nomor_invoice' => sprintf('INV/%s/%04d', $mulai->format('Y'), $this->nomorInvoice++),
            'pos_user_id' => $posUser->id,
            'langganan_id' => $langganan->id,
            'nominal' => self::HARGA[$durasi->value],
            'durasi' => $durasi,
            'metode' => MetodePembayaran::from($this->acak->pilihBerbobot([
                'TRANSFER_BANK' => 40,
                'QRIS' => 30,
                'VIRTUAL_ACCOUNT' => 20,
                'MANUAL' => 10,
            ])),
            'status' => $status,
            'tanggal' => $mulai,
            'dibayar_pada' => $status === StatusPembayaran::Lunas ? $mulai : null,
        ]);
    }

    /**
     * Sebaran yang dijanjikan seeder ini. Diekspos supaya uji verifikasi
     * membandingkan hasil terhadap angka yang sama, bukan salinannya.
     *
     * @return array<string, int>
     */
    public static function sebaran(): array
    {
        return self::SEBARAN;
    }
}
