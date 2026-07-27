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

export interface DetailPengguna {
    user: PosUserRingkas;
    riwayatLangganan: Langganan[];
    riwayatPembayaran: Pembayaran[];
    riwayatUnduhan: UnduhanRingkas[];
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

/** Dipakai dasbor: langganan terdekat berakhir. */
export function ambilAkanBerakhir(batas = 10): Promise<PosUserRingkas[]> {
    return ambil<PosUserRingkas[]>('/pengguna/akan-berakhir', { batas });
}
