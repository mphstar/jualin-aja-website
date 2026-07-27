import { useMediaQuery } from '@/hooks/useMediaQuery';

/** Ambang `md` Tailwind — dipakai komponen sidebar shadcn. */
const MOBILE_BREAKPOINT = 768;

/**
 * Versi bawaan shadcn menebak `false` lalu mengoreksinya di efek, yang membuat
 * sidebar sempat merender bentuk desktop di ponsel. Di sini ia meneruskan ke
 * `useMediaQuery` supaya seluruh aplikasi punya satu jalur deteksi lebar layar.
 */
export function useIsMobile(): boolean {
    return useMediaQuery(`(max-width: ${MOBILE_BREAKPOINT - 1}px)`);
}
