import { create } from 'zustand';
import { api } from '@/lib/api';
import type { HargaPaket, PengaturanMidtrans } from '@/lib/api';
import { HARGA_PAKET_DEFAULT } from '@/lib/konstanta';

export const PENGATURAN_MIDTRANS_DEFAULT: PengaturanMidtrans = {
    server_key: '',
    client_key: '',
    is_production: false,
    timeout: 15,
};

interface PengaturanState {
    hargaPaket: HargaPaket;
    midtrans: PengaturanMidtrans;
    memuat: boolean;
    menyimpan: boolean;
    memuatMidtrans: boolean;
    menyimpanMidtrans: boolean;
    sudahDimuat: boolean;
    sudahDimuatMidtrans: boolean;

    muat: () => Promise<void>;
    muatMidtrans: () => Promise<void>;
    simpanHarga: (harga: HargaPaket) => Promise<void>;
    simpanMidtrans: (midtrans: PengaturanMidtrans) => Promise<void>;
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
    midtrans: PENGATURAN_MIDTRANS_DEFAULT,
    memuat: false,
    menyimpan: false,
    memuatMidtrans: false,
    menyimpanMidtrans: false,
    sudahDimuat: false,
    sudahDimuatMidtrans: false,

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

    muatMidtrans: async () => {
        if (get().memuatMidtrans) {
            return;
        }

        set({ memuatMidtrans: true });

        try {
            set({
                midtrans: await api.pengaturan.ambilPengaturanMidtrans(),
                sudahDimuatMidtrans: true,
            });
        } finally {
            set({ memuatMidtrans: false });
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

    simpanMidtrans: async (midtrans) => {
        set({ menyimpanMidtrans: true });

        try {
            set({
                midtrans:
                    await api.pengaturan.simpanPengaturanMidtrans(midtrans),
                sudahDimuatMidtrans: true,
            });
        } finally {
            set({ menyimpanMidtrans: false });
        }
    },

    kembalikanHargaAwal: async () => {
        await get().simpanHarga(HARGA_PAKET_DEFAULT);
    },
}));
