import { ambil, kirim } from '@/lib/api/client';
import type {
    DurasiPaket,
    Halaman,
    JenisUsaha,
    Langganan,
    ParamsHalaman,
    Pembayaran,
    PosUser,
    PosUserRingkas,
    StatusLangganan,
    UnduhanRingkas,
} from '@/types';

export interface ParamsPengguna extends ParamsHalaman {
    status?: StatusLangganan | 'SEMUA';
    durasi?: DurasiPaket | 'SEMUA';
    jenisUsaha?: JenisUsaha | 'SEMUA';
}

/**
 * Seberapa hidup kasir sebuah toko.
 *
 * Dibedakan dari status langganan dengan sengaja: `aktif` di sini berarti
 * kasirnya benar-benar dipakai 30 hari terakhir, bukan langganannya masih
 * berjalan. Toko yang membayar tahunan lalu tidak pernah membuka aplikasinya
 * adalah toko yang tidak akan memperpanjang.
 */
export interface RingkasanPos {
    kategori: number;
    produk: number;
    produkHabis: number;
    transaksi30Hari: number;
    omzet30Hari: number;
    piutangJumlah: number;
    piutangTotal: number;
    transaksiTerakhir: string | null;
    aktif: boolean;
}

export interface DetailPengguna {
    user: PosUserRingkas;
    riwayatLangganan: Langganan[];
    riwayatPembayaran: Pembayaran[];
    riwayatUnduhan: UnduhanRingkas[];
    ringkasanPos: RingkasanPos;
}

export function ambilDaftarPengguna(
    params: ParamsPengguna = {},
): Promise<Halaman<PosUserRingkas>> {
    return ambil<Halaman<PosUserRingkas>>('/pengguna', { ...params });
}

export function ambilDetailPengguna(id: string): Promise<DetailPengguna> {
    return ambil<DetailPengguna>(`/pengguna/${id}`);
}

export function tangguhkanPengguna(
    id: string,
    alasan: string,
): Promise<PosUser> {
    return kirim<PosUser>(`/pengguna/${id}/tangguhkan`, { alasan });
}

export function pulihkanPengguna(id: string): Promise<PosUser> {
    return kirim<PosUser>(`/pengguna/${id}/pulihkan`);
}

export function tambahPengguna(data: DataTambahPengguna): Promise<PosUser> {
    return kirim<PosUser>('/pengguna', data);
}

/** Dipakai dasbor: langganan terdekat berakhir. */
export function ambilAkanBerakhir(batas = 10): Promise<PosUserRingkas[]> {
    return ambil<PosUserRingkas[]>('/pengguna/akan-berakhir', { batas });
}

export interface DataTambahPengguna {
    nama: string;
    email: string;
    telepon: string;
    namaToko: string;
    jenisUsaha: JenisUsaha;
    kota: string;
    alamat?: string;
    password: string;
    password_confirmation: string;
    langganan?: {
        durasi: DurasiPaket;
        sumber: string;
        lamaHari: number;
        catatan?: string;
    };
}
