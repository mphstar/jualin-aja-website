import { ambil, kirim, tambal } from '@/lib/api/client';
import type { Halaman } from '@/types';

export interface TiketData {
    id: number;
    nomorTiket: string;
    jenis: 'SARAN' | 'KOMPLAIN' | 'PERTANYAAN';
    jenisLabel: string;
    subjek: string;
    pesan: string;
    status: 'TERBUKA' | 'DIPROSES' | 'SELESAI' | 'DITUTUP';
    statusLabel: string;
    prioritas: 'RENDAH' | 'SEDANG' | 'TINGGI';
    prioritasLabel: string;
    balasanAdmin: string | null;
    dibalasPada: string | null;
    adminNama: string | null;
    toko: {
        id: number;
        nama: string;
        email: string;
        namaToko: string;
        jenisUsaha: string;
    };
    dibuatPada: string;
}

export interface ParamsTiket {
    cari?: string;
    jenis?: string;
    status?: string;
    halaman?: number;
}

export const ambilDaftarTiket = (params?: ParamsTiket) =>
    ambil<Halaman<TiketData>>(
        '/tiket',
        params as Record<string, string | number | boolean | undefined | null>,
    );

export const ambilDetailTiket = (id: string | number) =>
    ambil<TiketData>(`/tiket/${id}`);

export const kirimBalasanTiket = (
    id: string | number,
    balasan: string,
    status?: string,
) => kirim<TiketData>(`/tiket/${id}/respon`, { balasan, status });

export const ubahStatusTiket = (id: string | number, status: string) =>
    tambal<TiketData>(`/tiket/${id}/status`, { status });
