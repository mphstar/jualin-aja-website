import { create } from 'zustand';
import { api, KesalahanApi } from '@/lib/api';
import type { ParamsEbook } from '@/lib/api';
import type { Ebook, StatusEbook } from '@/types';

type Filter = Pick<
    ParamsEbook,
    'cari' | 'jenis' | 'kategori' | 'kategoriPrompt' | 'status'
>;
type Tampilan = 'grid' | 'tabel';

const FILTER_AWAL: Filter = {
    cari: '',
    jenis: 'SEMUA',
    kategori: 'SEMUA',
    kategoriPrompt: 'SEMUA',
    status: 'SEMUA',
};

interface EbookState {
    daftar: Ebook[];
    memuat: boolean;
    error: string | null;
    filter: Filter;
    tampilan: Tampilan;

    ambilDaftar: () => Promise<void>;
    setFilter: (patch: Partial<Filter>) => void;
    resetFilter: () => void;
    setTampilan: (t: Tampilan) => void;
    ubahStatus: (id: string, status: StatusEbook) => Promise<void>;
    hapus: (id: string) => Promise<void>;
}

export const useEbookStore = create<EbookState>()((set, get) => ({
    daftar: [],
    memuat: false,
    error: null,
    filter: FILTER_AWAL,
    tampilan: 'grid',

    ambilDaftar: async () => {
        set({ memuat: true, error: null });

        try {
            const hasil = await api.ebook.ambilDaftarEbook({
                ...get().filter,
                halaman: 1,
                perHalaman: 1000,
            });
            set({ daftar: hasil.data, memuat: false });
        } catch (e) {
            set({
                memuat: false,
                error:
                    e instanceof KesalahanApi
                        ? e.message
                        : 'Tidak dapat memuat katalog ebook.',
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

    setTampilan: (tampilan) => set({ tampilan }),

    ubahStatus: async (id, status) => {
        await api.ebook.ubahStatusEbook(id, status);
        await get().ambilDaftar();
    },

    hapus: async (id) => {
        await api.ebook.hapusEbook(id);
        await get().ambilDaftar();
    },
}));
