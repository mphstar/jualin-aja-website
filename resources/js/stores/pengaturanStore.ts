import { create } from 'zustand';
import { api } from '@/lib/api';
import type { HargaPaket, PengaturanMayar } from '@/lib/api';
import { HARGA_PAKET_DEFAULT } from '@/lib/konstanta';

export const PENGATURAN_MAYAR_DEFAULT: PengaturanMayar = {
    api_key: '',
    webhook_secret: '',
    is_production: false,
    timeout: 15,
};

interface PengaturanState {
    hargaPaket: HargaPaket;
    mayar: PengaturanMayar;
    memuat: boolean;
    menyimpan: boolean;
    memuatMayar: boolean;
    menyimpanMayar: boolean;
    sudahDimuat: boolean;
    sudahDimuatMayar: boolean;

    muat: () => Promise<void>;
    muatMayar: () => Promise<void>;
    simpanHarga: (harga: HargaPaket) => Promise<void>;
    simpanMayar: (mayar: PengaturanMayar) => Promise<void>;
    kembalikanHargaAwal: () => Promise<void>;
}

/**
 * Harga paket kini milik server (tabel `pengaturan`), bukan localStorage.
 *
 * Bedanya penting: harga yang dipakai dialog perpanjang harus sama untuk
 * siapa pun yang membuka panel, dan tetap sama setelah ganti peramban.
 * `HARGA_PAKET_DEFAULT` cuma nilai tampilan sementara sebelum muat pertama.
 */
export const usePengaturanStore = create<PengaturanState>()((set, get) => ({
    hargaPaket: HARGA_PAKET_DEFAULT,
    mayar: PENGATURAN_MAYAR_DEFAULT,
    memuat: false,
    menyimpan: false,
    memuatMayar: false,
    menyimpanMayar: false,
    sudahDimuat: false,
    sudahDimuatMayar: false,

    muat: async () => {
        if (get().memuat) {
            return;
        }

        set({ memuat: true });

        try {
            set({
                hargaPaket: await api.pengaturan.ambilHargaPaket(),
                sudahDimuat: true,
            });
        } finally {
            set({ memuat: false });
        }
    },

    muatMayar: async () => {
        if (get().memuatMayar) {
            return;
        }

        set({ memuatMayar: true });

        try {
            set({
                mayar: await api.pengaturan.ambilPengaturanMayar(),
                sudahDimuatMayar: true,
            });
        } finally {
            set({ memuatMayar: false });
        }
    },

    simpanHarga: async (harga) => {
        set({ menyimpan: true });

        try {
            set({
                hargaPaket: await api.pengaturan.simpanHargaPaket(harga),
                sudahDimuat: true,
            });
        } finally {
            set({ menyimpan: false });
        }
    },

    simpanMayar: async (mayar) => {
        set({ menyimpanMayar: true });

        try {
            set({
                mayar: await api.pengaturan.simpanPengaturanMayar(mayar),
                sudahDimuatMayar: true,
            });
        } finally {
            set({ menyimpanMayar: false });
        }
    },

    kembalikanHargaAwal: async () => {
        await get().simpanHarga(HARGA_PAKET_DEFAULT);
    },
}));