<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DurasiPaket;
use App\Enums\JenisKonten;
use App\Enums\JenisUsaha;
use App\Enums\StatusLangganan;
use App\Enums\SumberLangganan;
use App\Enums\VersiLangganan;
use App\Support\FiturLangganan;
use App\Support\KondisiLangganan;
use Carbon\CarbonInterface;
use Database\Factories\PosUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * Pemilik toko — tenant yang memakai aplikasi POS mobile.
 *
 * Ia Authenticatable, bukan Model biasa: aplikasi mobile masuk dengan token
 * Sanctum miliknya sendiri lewat guard `pos`. Guard itu punya provider
 * terpisah dari admin, sehingga token POS tidak pernah bisa menyentuh
 * `/api/v1/*` milik panel — dan sebaliknya.
 *
 * @property int $id
 * @property string $nama
 * @property string $email
 * @property string $telepon
 * @property string|null $avatar_url
 * @property string $nama_toko
 * @property JenisUsaha $jenis_usaha
 * @property string $kota
 * @property string|null $alamat
 * @property int $nomor_struk_terakhir
 * @property Carbon|null $terakhir_masuk
 * @property Carbon $tanggal_daftar
 * @property bool $ditangguhkan
 * @property string|null $alasan_penangguhan
 * @property int|null $langganan_berlaku_id
 * @property Carbon|null $langganan_berakhir_pada
 * @property DurasiPaket|null $langganan_durasi
 * @property SumberLangganan|null $langganan_sumber
 */
#[Fillable([
    'nama', 'email', 'telepon', 'avatar_url', 'nama_toko',
    'jenis_usaha', 'kota', 'alamat', 'tanggal_daftar', 'ditangguhkan',
    'alasan_penangguhan', 'password',
])]
#[Hidden(['password'])]
class PosUser extends Authenticatable
{
    /** @use HasFactory<PosUserFactory> */
    use HasApiTokens, HasFactory;

    protected $table = 'pos_users';

    /**
     * Dikosongkan untuk mematikan "ingat saya".
     *
     * Aplikasi mobile masuk dengan token Sanctum yang memang berumur panjang;
     * tidak ada sesi peramban yang perlu diingat. Membiarkan nilai bawaannya
     * berarti tabel ini butuh kolom `remember_token` yang tidak akan pernah
     * dibaca siapa pun.
     */
    protected $rememberTokenName = '';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'jenis_usaha' => JenisUsaha::class,
            'tanggal_daftar' => 'datetime',
            'terakhir_masuk' => 'datetime',
            'ditangguhkan' => 'boolean',
            'nomor_struk_terakhir' => 'integer',
            'langganan_berakhir_pada' => 'datetime',
            'langganan_durasi' => DurasiPaket::class,
            'langganan_sumber' => SumberLangganan::class,
            'password' => 'hashed',
        ];
    }

    /** @return HasMany<Langganan, $this> */
    public function langganan(): HasMany
    {
        return $this->hasMany(Langganan::class);
    }

    /**
     * Siklus yang sedang dipakai: tanggal berakhirnya paling jauh ke depan.
     * Dibaca lewat kolom cache, bukan subkueri — lihat migrasi pos_users.
     *
     * @return BelongsTo<Langganan, $this>
     */
    public function langgananBerlaku(): BelongsTo
    {
        return $this->belongsTo(Langganan::class, 'langganan_berlaku_id');
    }

    /** @return HasMany<Pembayaran, $this> */
    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    /** @return HasMany<UnduhanEbook, $this> */
    public function unduhan(): HasMany
    {
        return $this->hasMany(UnduhanEbook::class);
    }

    /** @return HasMany<AksesPustaka, $this> */
    public function aksesPustaka(): HasMany
    {
        return $this->hasMany(AksesPustaka::class);
    }

    /** @return HasMany<Kategori, $this> */
    public function kategori(): HasMany
    {
        return $this->hasMany(Kategori::class);
    }

    /** @return HasMany<Produk, $this> */
    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class);
    }

    /** @return HasMany<Transaksi, $this> */
    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    /** @return HasOne<PengaturanStruk, $this> */
    public function pengaturanStruk(): HasOne
    {
        return $this->hasOne(PengaturanStruk::class);
    }

    public function status(?CarbonInterface $sekarang = null): StatusLangganan
    {
        return KondisiLangganan::status(
            $this->langganan_berakhir_pada,
            $this->langganan_sumber,
            $this->ditangguhkan,
            $sekarang,
        );
    }

    public function sisaHari(?CarbonInterface $sekarang = null): int
    {
        if ($this->langganan_berakhir_pada === null) {
            return 0;
        }

        return KondisiLangganan::sisaHari($this->langganan_berakhir_pada, $sekarang);
    }

    public function versiLangganan(?CarbonInterface $sekarang = null): VersiLangganan
    {
        return FiturLangganan::versiDariStatus($this->status($sekarang));
    }

    public function bolehAksesResep(?CarbonInterface $sekarang = null): bool
    {
        return FiturLangganan::bolehAksesResep($this->versiLangganan($sekarang));
    }

    public function bolehAksesVoucher(?CarbonInterface $sekarang = null): bool
    {
        return FiturLangganan::bolehAksesVoucher($this->versiLangganan($sekarang));
    }

    public function bolehTransaksi(?CarbonInterface $sekarang = null): bool
    {
        return FiturLangganan::bolehTransaksi($this->versiLangganan($sekarang));
    }

    /**
     * Apakah toko memiliki akses ke ebook tertentu (klaim atau beli).
     */
    public function punyaAksesEbook(int $ebookId): bool
    {
        $akses = $this->aksesPustaka->firstWhere('ebook_id', $ebookId);

        if ($akses === null) {
            return false;
        }

        if ($akses->tipe_akses === AksesPustaka::TIPE_BELI_SATUAN) {
            return true;
        }

        // Akses via klaim langganan membutuhkan status langganan berbayar aktif
        return $this->versiLangganan() === VersiLangganan::Langganan;
    }

    /**
     * Sejak kapan jatah klaim siklus berjalan dihitung.
     *
     * Jatah klaim melekat pada SATU SIKLUS langganan, bukan pada umur akun:
     * tiap siklus baru — dari pelunasan invoice maupun perpanjangan manual
     * admin, keduanya lewat [PerpanjangLangganan] — membuka jatah 1 Resep + 1
     * Prompt lagi.
     *
     * Patokannya `created_at` baris `langganan` termuda, BUKAN `tanggal_mulai`.
     * Perpanjangan lebih awal menyambung dari akhir siklus lama, jadi
     * `tanggal_mulai` siklus baru justru ada di masa depan — memakainya akan
     * menunda jatah baru sampai siklus itu benar-benar mulai.
     */
    public function jatahKlaimMulaiSejak(): ?CarbonInterface
    {
        return $this->langganan()->latest('id')->value('created_at');
    }

    /**
     * Apakah toko sudah mengklaim 1 gratis untuk jenis konten ini (Resep / Prompt)
     * pada siklus langganan yang sedang berjalan.
     *
     * Memakai query, bukan koleksi `aksesPustaka` yang mungkin sudah dimuat:
     * batas waktunya harus dihitung di database, dan pemanggilnya cuma sekali
     * per jenis.
     */
    public function sudahKlaimJenis(JenisKonten $jenis): bool
    {
        $sejak = $this->jatahKlaimMulaiSejak();

        if ($sejak === null) {
            return false;
        }

        return $this->aksesPustaka()
            ->where('jenis', $jenis->value)
            ->where('tipe_akses', AksesPustaka::TIPE_KLAIM_LANGGANAN)
            ->where('created_at', '>=', $sejak)
            ->exists();
    }

    /**
     * Apakah toko berhak mengklaim 1 gratis untuk jenis konten ini (Resep / Prompt).
     */
    public function bolehKlaimJenis(JenisKonten $jenis): bool
    {
        return $this->versiLangganan() === VersiLangganan::Langganan
            && ! $this->sudahKlaimJenis($jenis);
    }

    public function batasMaksimalProduk(?CarbonInterface $sekarang = null): ?int
    {
        return FiturLangganan::batasMaksimalProduk($this->versiLangganan($sekarang));
    }

    public function bolehTambahProduk(int $jumlahTerpasang = 0, int $tambahan = 1, ?CarbonInterface $sekarang = null): bool
    {
        return FiturLangganan::bolehTambahProduk($this->versiLangganan($sekarang), $jumlahTerpasang, $tambahan);
    }

    /**
     * Boleh memakai kasir?
     *
     * Hanya toko nonaktif (ditangguhkan admin) yang tertutup. Kedaluwarsa
     * (Gratis) dan Trial tetap boleh mencatat transaksi — dan halaman
     * langganan serta pembayaran selalu terbuka, karena aplikasi yang
     * mengunci pintu keluarnya sendiri tidak bisa diperpanjang.
     */
    public function langgananBerjalan(?CarbonInterface $sekarang = null): bool
    {
        return ! $this->ditangguhkan && $this->status($sekarang) !== StatusLangganan::Nonaktif;
    }

    /**
     * Segarkan kolom ringkasan langganan.
     *
     * Satu-satunya penulis kolom cache di tabel ini. Dipanggil dari
     * LanggananObserver, bukan dari controller — kalau boleh dipanggil dari
     * mana saja, satu jalur yang terlewat membuat filter status berbohong.
     */
    public function segarkanRingkasanLangganan(): void
    {
        $berlaku = $this->langganan()
            ->orderByDesc('tanggal_berakhir')
            ->orderByDesc('id')
            ->first();

        $this->forceFill([
            'langganan_berlaku_id' => $berlaku?->id,
            'langganan_berakhir_pada' => $berlaku?->tanggal_berakhir,
            'langganan_durasi' => $berlaku?->durasi,
            'langganan_sumber' => $berlaku?->sumber,
        ])->saveQuietly();
    }
}
