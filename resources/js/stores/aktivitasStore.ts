import { create } from 'zustand';
import { api, KesalahanApi } from '@/lib/api';
import type { ParamsAktivitas } from '@/lib/api';
import type { LogAktivitas } from '@/types';

type Filter = Pick<ParamsAktivitas, 'cari' | 'aksi' | 'dari' | 'sampai'>;

const PER_MUATAN = 25;

const FILTER_AWAL: Filter = {
    cari: '',
    aksi: 'SEMUA',
    dari: undefined,
    sampai: undefined,
};

interface AktivitasState {
    daftar: LogAktivitas[];
    total: number;
    batas: number;
    memuat: boolean;
    memuatLagi: boolean;
    error: string | null;
    filter: Filter;

    ambilDaftar: () => Promise<void>;
    muatLebihBanyak: () => Promise<void>;
    setFilter: (patch: Partial<Filter>) => void;
    resetFilter: () => void;
}

export const useAktivitasStore = create<AktivitasState>()((set, get) => ({
    daftar: [],
    total: 0,
    batas: PER_MUATAN,
    memuat: false,
    memuatLagi: false,
    error: null,
    filter: FILTER_AWAL,

    ambilDaftar: async () => {
        set({ memuat: true, error: null });

        try {
            const hasil = await api.aktivitas.ambilAktivitas({
                ...get().filter,
                halaman: 1,
                perHalaman: get().batas,
            });
            set({ daftar: hasil.data, total: hasil.total, memuat: false });
        } catch (e) {
            set({
                memuat: false,
                error:
                    e instanceof KesalahanApi
                        ? e.message
                        : 'Tidak dapat memuat log aktivitas.',
            });
        }
    },

    muatLebihBanyak: async () => {
        set({ batas: get().batas + PER_MUATAN, memuatLagi: true });
        await get().ambilDaftar();
        set({ memuatLagi: false });
    },

    setFilter: (patch) => {
        // Ganti filter selalu mengulang dari halaman pertama.
        set({ filter: { ...get().filter, ...patch }, batas: PER_MUATAN });
        void get().ambilDaftar();
    },

    resetFilter: () => {
        set({ filter: FILTER_AWAL, batas: PER_MUATAN });
        void get().ambilDaftar();
    },
}));
