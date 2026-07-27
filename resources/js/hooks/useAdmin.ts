import { usePage } from '@inertiajs/react';
import type { AdminUser } from '@/types';

/**
 * Admin yang sedang masuk, dibaca dari shared prop Inertia.
 *
 * Sengaja BUKAN dari store: sesi dipegang server, dan menyalinnya ke store
 * berarti dua sumber kebenaran yang bisa menyimpang — persis bug yang muncul
 * kalau profil diubah lalu sidebar masih menampilkan nama lama.
 *
 * `null` hanya di halaman login dan halaman galat untuk tamu.
 */
export function useAdmin(): AdminUser | null {
    return usePage().props.auth?.admin ?? null;
}
