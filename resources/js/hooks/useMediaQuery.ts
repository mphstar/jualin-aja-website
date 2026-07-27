import { useCallback, useSyncExternalStore } from 'react';

/**
 * Pantau media query CSS dari React.
 *
 * Memakai `useSyncExternalStore`, bukan useState+useEffect: nilai awal dibaca
 * langsung dari peramban pada render pertama, jadi tabel tidak sempat berkedip
 * dalam bentuk desktop di layar ponsel — dan tidak ada render tambahan yang
 * hanya untuk mengoreksi tebakan awal.
 */
export function useMediaQuery(query: string): boolean {
    const berlangganan = useCallback(
        (beriTahu: () => void) => {
            const mql = window.matchMedia(query);
            mql.addEventListener('change', beriTahu);

            return () => mql.removeEventListener('change', beriTahu);
        },
        [query],
    );

    return useSyncExternalStore(
        berlangganan,
        () => window.matchMedia(query).matches,
        // Nilai saat SSR: anggap layar besar. Panel ini hanya dirender di
        // peramban, jadi cabang ini praktis tidak terpakai.
        () => false,
    );
}

/** Ambang yang sama dengan breakpoint `md` Tailwind. */
export function useLayarKecil(): boolean {
    return useMediaQuery('(max-width: 767px)');
}
