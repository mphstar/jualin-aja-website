/**
 * Titik masuk tunggal lapisan data.
 *
 * ⭐ SATU-SATUNYA FOLDER YANG BICARA HTTP.
 *
 * Store dan komponen memanggil `api.*` dan tidak pernah menyentuh `fetch`
 * langsung. Itulah yang membuat perpindahan dari data mock ke backend Laravel
 * kemarin hanya menyentuh folder ini — bentuk kembaliannya (termasuk amplop
 * `Halaman<T>` untuk daftar) sengaja dibuat identik dengan yang dikirim API.
 */
import * as aktivitas from '@/lib/api/aktivitas';
import * as auth from '@/lib/api/auth';
import * as ebook from '@/lib/api/ebook';
import * as langganan from '@/lib/api/langganan';
import * as pembayaran from '@/lib/api/pembayaran';
import * as pengaturan from '@/lib/api/pengaturan';
import * as pengguna from '@/lib/api/pengguna';
import * as statistik from '@/lib/api/statistik';

export const api = {
    aktivitas,
    auth,
    ebook,
    langganan,
    pembayaran,
    pengaturan,
    pengguna,
    statistik,
};

export { KesalahanApi } from '@/lib/api/client';
export type { ParamsPengguna, DetailPengguna } from '@/lib/api/pengguna';
export type { ParamsLangganan } from '@/lib/api/langganan';
export type { ParamsEbook, MasukanEbook, DetailEbook } from '@/lib/api/ebook';
export type {
    ParamsPembayaran,
    RingkasanPembayaran,
    DetailInvoice,
} from '@/lib/api/pembayaran';
export type { ParamsAktivitas } from '@/lib/api/aktivitas';
export type { HargaPaket } from '@/lib/api/pengaturan';
export type { MasukanMasuk, HasilMasuk } from '@/lib/api/auth';
