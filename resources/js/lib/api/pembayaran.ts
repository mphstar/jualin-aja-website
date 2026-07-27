import { ambil, kirim } from '@/lib/api/client';
import type {
    Halaman,
    MetodePembayaran,
    ParamsHalaman,
    Pembayaran,
    PembayaranRingkas,
    PosUser,
    StatusPembayaran,
} from '@/types';

export interface ParamsPembayaran extends ParamsHalaman {
    status?: StatusPembayaran | 'SEMUA';
    metode?: MetodePembayaran | 'SEMUA';
    dari?: string;
    sampai?: string;
}

export interface RingkasanPembayaran {
    totalLunasBulanIni: number;
    jumlahMenunggu: number;
    jumlahGagal: number;
}

export interface DetailInvoice {
    pembayaran: PembayaranRingkas;
    user: PosUser;
}

export function ambilDaftarPembayaran(
    params: ParamsPembayaran = {},
): Promise<Halaman<PembayaranRingkas>> {
    return ambil<Halaman<PembayaranRingkas>>('/pembayaran', { ...params });
}

export function ambilRingkasanPembayaran(): Promise<RingkasanPembayaran> {
    return ambil<RingkasanPembayaran>('/pembayaran/ringkasan');
}

export function ambilDetailInvoice(id: string): Promise<DetailInvoice> {
    return ambil<DetailInvoice>(`/pembayaran/${id}`);
}

/**
 * Tandai lunas (PRD §F6.7) — server sekaligus memperpanjang langganan tokonya
 * dan menulis dua entri log. Jalur yang sama nanti dipakai webhook Midtrans.
 */
export function tandaiLunas(id: string): Promise<Pembayaran> {
    return kirim<Pembayaran>(`/pembayaran/${id}/tandai-lunas`);
}

export function tandaiGagal(id: string): Promise<Pembayaran> {
    return kirim<Pembayaran>(`/pembayaran/${id}/tandai-gagal`);
}
