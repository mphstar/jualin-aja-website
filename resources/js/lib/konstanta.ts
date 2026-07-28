import type {
    DurasiPaket,
    JenisAksi,
    JenisUsaha,
    KategoriEbook,
    MetodePembayaran,
    StatusEbook,
    StatusLangganan,
    StatusPembayaran,
    SumberLangganan,
} from '@/types';

/** Ambang "akan berakhir" dalam hari — dipakai dasbor & badge status. */
export const AMBANG_AKAN_BERAKHIR = 7;

/** Lama masa uji coba saat user baru mendaftar. */
export const LAMA_TRIAL_HARI = 14;

/** Berapa bulan yang ditambahkan tiap durasi paket. */
export const BULAN_PER_DURASI: Record<DurasiPaket, number> = {
    TRIAL: 0,
    BULANAN: 1,
    SEMESTERAN: 6,
    TAHUNAN: 12,
};

/**
 * Harga contoh — bisa diubah dari halaman Pengaturan.
 * Sengaja ditaruh di satu tempat supaya seed & UI tidak pernah berbeda.
 */
export const HARGA_PAKET_DEFAULT: Record<DurasiPaket, number> = {
    TRIAL: 0,
    BULANAN: 99_000,
    SEMESTERAN: 499_000,
    TAHUNAN: 899_000,
};

/**
 * Kredensial akun yang dibuat DatabaseSeeder. Ditampilkan di halaman login
 * karena ini lingkungan pengembangan — hapus blok hint-nya sebelum rilis.
 * Nilainya harus sama dengan Database\Seeders\DatabaseSeeder::EMAIL_ADMIN.
 */
export const KREDENSIAL_DEMO = {
    email: 'admin@jualinaja.id',
    kataSandi: 'admin123',
} as const;

// ============================================================
// Label Bahasa Indonesia untuk seluruh union
// ============================================================

export const LABEL_DURASI: Record<DurasiPaket, string> = {
    TRIAL: 'Uji Coba',
    BULANAN: '1 Bulan',
    SEMESTERAN: '6 Bulan',
    TAHUNAN: '12 Bulan',
};

export const LABEL_STATUS_LANGGANAN: Record<StatusLangganan, string> = {
    TRIAL: 'Uji Coba',
    AKTIF: 'Aktif',
    AKAN_BERAKHIR: 'Akan Berakhir',
    KEDALUWARSA: 'Kedaluwarsa',
    NONAKTIF: 'Nonaktif',
};

export const LABEL_SUMBER_LANGGANAN: Record<SumberLangganan, string> = {
    TRIAL: 'Uji Coba',
    PEMBELIAN: 'Pembelian',
    PERPANJANGAN_MANUAL: 'Perpanjangan Manual',
    HADIAH: 'Hadiah',
};

export const LABEL_STATUS_PEMBAYARAN: Record<StatusPembayaran, string> = {
    LUNAS: 'Lunas',
    MENUNGGU: 'Menunggu',
    GAGAL: 'Gagal',
    KEDALUWARSA: 'Kedaluwarsa',
    REFUND: 'Refund',
};

export const LABEL_METODE_PEMBAYARAN: Record<MetodePembayaran, string> = {
    TRANSFER_BANK: 'Transfer Bank',
    QRIS: 'QRIS',
    VIRTUAL_ACCOUNT: 'Virtual Account',
    EWALLET: 'E-Wallet',
    MANUAL: 'Manual / Tunai',
};

export const LABEL_KATEGORI_EBOOK: Record<KategoriEbook, string> = {
    MINUMAN: 'Minuman',
    MAKANAN_BERAT: 'Makanan Berat',
    SNACK: 'Snack',
    DESSERT: 'Dessert',
    BAKERY: 'Bakery',
    BUMBU_SAUS: 'Bumbu & Saus',
};

export const LABEL_STATUS_EBOOK: Record<StatusEbook, string> = {
    DRAF: 'Draf',
    TERBIT: 'Terbit',
};

export const LABEL_JENIS_USAHA: Record<JenisUsaha, string> = {
    KAFE: 'Kafe',
    RESTORAN: 'Restoran',
    WARUNG_MAKAN: 'Warung Makan',
    BAKERY: 'Bakery',
    TOKO_KELONTONG: 'Toko Kelontong',
    LAINNYA: 'Lainnya',
};

export const LABEL_AKSI: Record<JenisAksi, string> = {
    MASUK: 'Masuk',
    KELUAR: 'Keluar',
    LANGGANAN_PERPANJANG: 'Perpanjang Langganan',
    USER_TANGGUHKAN: 'Tangguhkan User',
    USER_PULIHKAN: 'Pulihkan User',
    EBOOK_TAMBAH: 'Tambah Ebook',
    EBOOK_UBAH: 'Ubah Ebook',
    EBOOK_TERBITKAN: 'Terbitkan Ebook',
    EBOOK_JADIKAN_DRAF: 'Jadikan Draf',
    EBOOK_HAPUS: 'Hapus Ebook',
    PEMBAYARAN_LUNAS: 'Tandai Lunas',
    PEMBAYARAN_GAGAL: 'Tandai Gagal',
    PENGATURAN_UBAH: 'Ubah Pengaturan',
};

// Daftar siap pakai untuk komponen <Select> / filter.
export const DAFTAR_DURASI = Object.keys(LABEL_DURASI) as DurasiPaket[];
export const DAFTAR_STATUS_LANGGANAN = Object.keys(
    LABEL_STATUS_LANGGANAN,
) as StatusLangganan[];
export const DAFTAR_KATEGORI_EBOOK = Object.keys(
    LABEL_KATEGORI_EBOOK,
) as KategoriEbook[];
export const DAFTAR_JENIS_USAHA = Object.keys(
    LABEL_JENIS_USAHA,
) as JenisUsaha[];
export const DAFTAR_STATUS_PEMBAYARAN = Object.keys(
    LABEL_STATUS_PEMBAYARAN,
) as StatusPembayaran[];
export const DAFTAR_METODE_PEMBAYARAN = Object.keys(
    LABEL_METODE_PEMBAYARAN,
) as MetodePembayaran[];
export const DAFTAR_AKSI = Object.keys(LABEL_AKSI) as JenisAksi[];
