/**
 * Tipe domain Admin POS.
 *
 * Catatan: proyek ini memakai `erasableSyntaxOnly`, jadi TIDAK boleh pakai `enum`.
 * Semua himpunan nilai ditulis sebagai union string + konstanta biasa.
 */

// ============================================================
// Union nilai
// ============================================================

export type DurasiPaket = 'TRIAL' | 'BULANAN' | 'SEMESTERAN' | 'TAHUNAN';

export type StatusLangganan =
    'TRIAL' | 'AKTIF' | 'AKAN_BERAKHIR' | 'KEDALUWARSA' | 'NONAKTIF';

export type SumberLangganan =
    'TRIAL' | 'PEMBELIAN' | 'PERPANJANGAN_MANUAL' | 'HADIAH';

export type StatusPembayaran =
    'LUNAS' | 'MENUNGGU' | 'GAGAL' | 'KEDALUWARSA' | 'REFUND';

export type MetodePembayaran =
    'TRANSFER_BANK' | 'QRIS' | 'VIRTUAL_ACCOUNT' | 'EWALLET' | 'MANUAL';

export type KategoriEbook =
    'MINUMAN' | 'MAKANAN_BERAT' | 'SNACK' | 'DESSERT' | 'BAKERY' | 'BUMBU_SAUS';

export type KategoriPrompt =
    | 'LOGO'
    | 'DESAIN_MENU'
    | 'POSTER_PROMOSI'
    | 'SOSIAL_MEDIA'
    | 'FOTO_PRODUK'
    | 'KEMASAN_PRODUK';

export type JenisKonten = 'RESEP' | 'PROMPT';

export type StatusEbook = 'DRAF' | 'TERBIT';

export type JenisUsaha =
    | 'KAFE'
    | 'RESTORAN'
    | 'WARUNG_MAKAN'
    | 'BAKERY'
    | 'TOKO_KELONTONG'
    | 'LAINNYA';

export type TargetAksi =
    'USER' | 'EBOOK' | 'LANGGANAN' | 'PEMBAYARAN' | 'SISTEM';

export type JenisAksi =
    | 'MASUK'
    | 'KELUAR'
    | 'LANGGANAN_PERPANJANG'
    | 'USER_TANGGUHKAN'
    | 'USER_PULIHKAN'
    | 'EBOOK_TAMBAH'
    | 'EBOOK_UBAH'
    | 'EBOOK_TERBITKAN'
    | 'EBOOK_JADIKAN_DRAF'
    | 'EBOOK_HAPUS'
    | 'PEMBAYARAN_LUNAS'
    | 'PEMBAYARAN_GAGAL'
    | 'PENGATURAN_UBAH';

// ============================================================
// Entitas
// ============================================================

export interface AdminUser {
    id: string;
    nama: string;
    email: string;
    avatarUrl?: string;
    terakhirMasuk: string; // ISO
}

/** Pemilik toko — pengguna aplikasi POS mobile. Tidak pernah membuka web ini. */
export interface PosUser {
    id: string;
    nama: string;
    email: string;
    telepon: string;
    avatarUrl?: string;
    namaToko: string;
    jenisUsaha: JenisUsaha;
    kota: string;
    tanggalDaftar: string; // ISO
    ditangguhkan: boolean;
    alasanPenangguhan?: string;
}

export interface Langganan {
    id: string;
    userId: string;
    durasi: DurasiPaket;
    sumber: SumberLangganan;
    tanggalMulai: string; // ISO
    tanggalBerakhir: string; // ISO
    dibuatOleh?: string;
    catatan?: string;
}

export interface Pembayaran {
    id: string;
    nomorInvoice: string;
    userId: string;
    langgananId?: string;
    nominal: number; // rupiah penuh, bukan sen
    durasi: DurasiPaket;
    metode: MetodePembayaran;
    status: StatusPembayaran;
    tanggal: string; // ISO
    catatan?: string;
}

export interface Ebook {
    id: string;
    jenis: JenisKonten;
    judul: string;
    slug: string;
    kategori?: KategoriEbook;
    kategoriPrompt?: KategoriPrompt;
    deskripsi: string;
    coverUrl?: string;
    fileUrl?: string;
    namaFile?: string;
    ukuranFileBytes?: number;
    jumlahHalaman?: number;
    status: StatusEbook;
    tanggalDibuat: string; // ISO
    tanggalTerbit?: string; // ISO
    jumlahUnduhan: number; // didenormalisasi supaya tabel tidak perlu menghitung
}

export interface UnduhanEbook {
    id: string;
    ebookId: string;
    userId: string;
    tanggal: string; // ISO
}

export interface LogAktivitas {
    id: string;
    waktu: string; // ISO
    aktorId: string;
    aktorNama: string;
    aksi: JenisAksi;
    targetTipe: TargetAksi;
    targetId?: string;
    targetLabel?: string;
    deskripsi: string;
}

// ============================================================
// Bentuk turunan (dihitung, tidak disimpan)
// ============================================================

/** PosUser + kondisi langganannya. Inilah yang dikonsumsi tabel & kartu. */
export interface PosUserRingkas extends PosUser {
    langgananAktif: Langganan | null;
    status: StatusLangganan;
    sisaHari: number;
    durasi: DurasiPaket | null;
}

/** Langganan + identitas pemiliknya, untuk tabel /langganan. */
export interface LanggananRingkas extends Langganan {
    namaUser: string;
    namaToko: string;
    avatarUrl?: string;
    status: StatusLangganan;
    sisaHari: number;
    /** Siklus yang sedang dipakai user; `false` berarti riwayat lama. */
    berlaku: boolean;
}

export interface PembayaranRingkas extends Pembayaran {
    namaUser: string;
    namaToko: string;
    avatarUrl?: string;
}

export interface UnduhanRingkas extends UnduhanEbook {
    namaUser: string;
    namaToko: string;
    judulEbook: string;
}

// ============================================================
// Statistik dasbor
// ============================================================

export interface TitikDeret {
    label: string; // "Jan", "Feb", ...
    nilai: number;
}

export interface IrisanPaket {
    durasi: DurasiPaket;
    jumlah: number;
}

export interface StatistikDasbor {
    totalUser: number;
    deltaUserPersen: number;
    langgananAktif: number;
    deltaAktifPersen: number;
    akanBerakhir: number;
    kedaluwarsa: number;
    pendapatanBulanIni: number;
    deltaPendapatanPersen: number;
    /** Toko yang kasirnya benar-benar dipakai 30 hari terakhir. */
    tokoMemakaiKasir: number;
}

// ============================================================
// Amplop transport — sengaja sudah berhalaman sejak awal supaya
// pindah ke paginasi sisi server nanti tidak merombak tabel.
// ============================================================

export interface Halaman<T> {
    data: T[];
    total: number;
    halaman: number;
    perHalaman: number;
}

export interface ParamsHalaman {
    halaman?: number;
    perHalaman?: number;
    cari?: string;
    urutKolom?: string;
    urutArah?: 'asc' | 'desc';
}
