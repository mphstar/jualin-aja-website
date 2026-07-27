import { create } from 'zustand';
import { api } from '@/lib/api';
import type { HargaPaket } from '@/lib/api';
import { HARGA_PAKET_DEFAULT } from '@/lib/konstanta';

interface PengaturanState {
    hargaPaket: HargaPaket;
    memuat: boolean;
    menyimpan: boolean;
    sudahDimuat: boolean;

    muat: () => Promise<void>;
    simpanHarga: (harga: HargaPaket) => Promise<void>;
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
    memuat: false,
    menyimpan: false,
    sudahDimuat: false,

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

    kembalikanHargaAwal: async () => {
        await get().simpanHarga(HARGA_PAKET_DEFAULT);
    },
}));
