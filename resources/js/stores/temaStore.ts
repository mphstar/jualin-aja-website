import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export type ModeTema = 'terang' | 'gelap' | 'sistem';

interface TemaState {
    tema: ModeTema;
    setTema: (tema: ModeTema) => void;
}

/**
 * Kunci localStorage ini HARUS sama dengan yang dibaca skrip anti-kedip
 * di index.html. Kalau salah satu diubah, tema akan berkedip putih
 * sekejap sebelum React memuat.
 */
export const KUNCI_TEMA = 'tema-admin-pos';

export const useTemaStore = create<TemaState>()(
    persist(
        (set) => ({
            tema: 'sistem',
            setTema: (tema) => set({ tema }),
        }),
        { name: KUNCI_TEMA },
    ),
);

/** Terjemahkan pilihan pengguna jadi terang/gelap konkret. */
export function tentukanGelap(tema: ModeTema): boolean {
    if (tema === 'gelap') {
        return true;
    }

    if (tema === 'terang') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}
