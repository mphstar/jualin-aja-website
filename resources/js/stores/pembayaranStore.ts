import { create } from 'zustand';
import { api, KesalahanApi } from '@/lib/api';
import type { ParamsPembayaran, RingkasanPembayaran } from '@/lib/api';
import type { PembayaranRingkas } from '@/types';

type Filter = Pick<
    ParamsPembayaran,
    'cari' | 'status' | 'metode' | 'tipe' | 'dari' | 'sampai'
>;

const FILTER_AWAL: Filter = {
    cari: '',
    status: 'SEMUA',
    metode: 'SEMUA',
    tipe: 'SEMUA',
    dari: undefined,
    sampai: undefined,
};

const RINGKASAN_KOSONG: RingkasanPembayaran = {
    totalLunasBulanIni: 0,
    jumlahMenunggu: 0,
    jumlahGagal: 0,
};

interface PembayaranState {
    daftar: PembayaranRingkas[];
    ringkasan: RingkasanPembayaran;
    memuat: boolean;
    error: string | null;
    filter: Filter;

    ambilDaftar: () => Promise<void>;
    setFilter: (patch: Partial<Filter>) => void;
    resetFilter: () => void;
    tandaiLunas: (id: string) => Promise<void>;
    tandaiGagal: (id: string) => Promise<void>;
}

export const usePembayaranStore = create<PembayaranState>()((set, get) => ({
    daftar: [],
    ringkasan: RINGKASAN_KOSONG,
    memuat: false,
    error: null,
    filter: FILTER_AWAL,

    ambilDaftar: async () => {
        set({ memuat: true, error: null });

        try {
            const [hasil, ringkasan] = await Promise.all([
                api.pembayaran.ambilDaftarPembayaran({
                    ...get().filter,
                    halaman: 1,
                    perHalaman: 1000,
                }),
                api.pembayaran.ambilRingkasanPembayaran(),
            ]);
            set({ daftar: hasil.data, ringkasan, memuat: false });
        } catch (e) {
            set({
                memuat: false,
                error:
                    e instanceof KesalahanApi
                        ? e.message
                        : 'Tidak dapat memuat riwayat pembayaran.',
            });
        }
    },

    setFilter: (patch) => {
        set({ filter: { ...get().filter, ...patch } });
        void get().ambilDaftar();
    },

    resetFilter: () => {
        set({ filter: FILTER_AWAL });
        void get().ambilDaftar();
    },

    tandaiLunas: async (id) => {
        await api.pembayaran.tandaiLunas(id);
        await get().ambilDaftar();
    },

    tandaiGagal: async (id) => {
        await api.pembayaran.tandaiGagal(id);
        await get().ambilDaftar();
    },
}));
