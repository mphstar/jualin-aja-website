import { create } from 'zustand';
import { api, KesalahanApi } from '@/lib/api';
import type { ParamsLangganan } from '@/lib/api';
import type { LanggananRingkas, StatusLangganan } from '@/types';

type Filter = Pick<
    ParamsLangganan,
    'cari' | 'status' | 'durasi' | 'termasukRiwayat'
>;

type Jumlah = Record<StatusLangganan | 'SEMUA', number>;

const FILTER_AWAL: Filter = {
    cari: '',
    status: 'SEMUA',
    durasi: 'SEMUA',
    termasukRiwayat: false,
};

const JUMLAH_KOSONG: Jumlah = {
    SEMUA: 0,
    AKTIF: 0,
    AKAN_BERAKHIR: 0,
    KEDALUWARSA: 0,
    TRIAL: 0,
    NONAKTIF: 0,
};

interface LanggananState {
    daftar: LanggananRingkas[];
    jumlah: Jumlah;
    memuat: boolean;
    error: string | null;
    filter: Filter;

    ambilDaftar: () => Promise<void>;
    setFilter: (patch: Partial<Filter>) => void;
    resetFilter: () => void;
}

export const useLanggananStore = create<LanggananState>()((set, get) => ({
    daftar: [],
    jumlah: JUMLAH_KOSONG,
    memuat: false,
    error: null,
    filter: FILTER_AWAL,

    ambilDaftar: async () => {
        set({ memuat: true, error: null });
        const { termasukRiwayat } = get().filter;

        try {
            // Daftar & jumlah diambil bersamaan supaya angka di tab tidak pernah
            // tertinggal satu langkah dari isi tabel.
            const [hasil, jumlah] = await Promise.all([
                api.langganan.ambilDaftarLangganan({
                    ...get().filter,
                    halaman: 1,
                    perHalaman: 1000,
                }),
                api.langganan.ambilJumlahPerStatus(termasukRiwayat),
            ]);
            set({ daftar: hasil.data, jumlah, memuat: false });
        } catch (e) {
            set({
                memuat: false,
                error:
                    e instanceof KesalahanApi
                        ? e.message
                        : 'Tidak dapat memuat daftar langganan.',
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
}));
