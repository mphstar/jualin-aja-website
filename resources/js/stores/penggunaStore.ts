import { create } from 'zustand';
import { api, KesalahanApi } from '@/lib/api';
import type { DataTambahPengguna, ParamsPengguna } from '@/lib/api';
import type { PosUserRingkas } from '@/types';

type Filter = Pick<ParamsPengguna, 'cari' | 'status' | 'durasi' | 'jenisUsaha'>;

const FILTER_AWAL: Filter = {
    cari: '',
    status: 'SEMUA',
    durasi: 'SEMUA',
    jenisUsaha: 'SEMUA',
};

interface PenggunaState {
    daftar: PosUserRingkas[];
    memuat: boolean;
    error: string | null;
    filter: Filter;

    ambilDaftar: () => Promise<void>;
    setFilter: (patch: Partial<Filter>) => void;
    resetFilter: () => void;
    tangguhkan: (id: string, alasan: string) => Promise<void>;
    pulihkan: (id: string) => Promise<void>;
    tambahPengguna: (data: DataTambahPengguna) => Promise<void>;
}

export const usePenggunaStore = create<PenggunaState>()((set, get) => ({
    daftar: [],
    memuat: false,
    error: null,
    filter: FILTER_AWAL,

    ambilDaftar: async () => {
        set({ memuat: true, error: null });

        try {
            // Paginasi dilakukan di sisi klien oleh DataTable, jadi ambil
            // semua baris yang lolos filter. Amplop `Halaman<T>` tetap dipakai
            // supaya pindah ke paginasi sisi server nanti tidak mengubah bentuk data.
            const hasil = await api.pengguna.ambilDaftarPengguna({
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
                        : 'Tidak dapat memuat daftar pengguna.',
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

    tangguhkan: async (id, alasan) => {
        await api.pengguna.tangguhkanPengguna(id, alasan);
        await get().ambilDaftar();
    },

    pulihkan: async (id) => {
        await api.pengguna.pulihkanPengguna(id);
        await get().ambilDaftar();
    },

    tambahPengguna: async (data) => {
        await api.pengguna.tambahPengguna(data);
        await get().ambilDaftar();
    },
}));
