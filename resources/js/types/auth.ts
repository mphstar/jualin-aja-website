import type { AdminUser } from '@/types';

/**
 * Satu-satunya prop yang dititipkan lewat Inertia (lihat
 * app/Http/Middleware/HandleInertiaRequests.php). Sisanya diambil komponen
 * sendiri dari `/api/v1`.
 *
 * `admin` bernilai null di halaman login — satu-satunya halaman untuk tamu.
 */
export type Auth = {
    admin: AdminUser | null;
};
