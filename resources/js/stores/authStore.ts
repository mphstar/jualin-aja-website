import { create } from 'zustand';
import { api, KesalahanApi } from '@/lib/api';

interface AuthState {
    memuat: boolean;
    error: string | null;

    /** Mengembalikan alamat tujuan bila berhasil, `null` bila gagal. */
    masuk: (email: string, kataSandi: string) => Promise<string | null>;
    keluar: () => Promise<void>;
    bersihkanError: () => void;
}

/**
 * Store ini HANYA memegang keadaan sementara formulir masuk.
 *
 * Identitas admin tidak disimpan di sini: sesinya milik server dan dikirim
 * sebagai shared prop Inertia, dibaca lewat `useAdmin()`. Menyalinnya ke store
 * (apalagi ke localStorage seperti versi mock) berarti dua sumber kebenaran —
 * dan yang di klien akan tetap mengaku "sudah masuk" setelah cookie sesinya
 * habis, sementara setiap permintaan data ditolak 401.
 */
export const useAuthStore = create<AuthState>()((set) => ({
    memuat: false,
    error: null,

    masuk: async (email, kataSandi) => {
        set({ memuat: true, error: null });

        try {
            const hasil = await api.auth.masuk({ email, kataSandi });
            set({ memuat: false });

            return hasil.tujuan;
        } catch (e) {
            set({
                memuat: false,
                error:
                    e instanceof KesalahanApi
                        ? e.message
                        : 'Terjadi kesalahan. Coba lagi.',
            });

            return null;
        }
    },

    keluar: async () => {
        await api.auth.keluar();
        set({ error: null });
    },

    bersihkanError: () => set({ error: null }),
}));
