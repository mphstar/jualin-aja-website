import { Head } from '@inertiajs/react';
import { tanpaShell } from '@/components/layout/denganShell';
import { HalamanLogin } from '@/features/auth/HalamanLogin';

/**
 * Satu-satunya halaman tanpa AppShell — dan satu-satunya yang boleh dibuka
 * tamu. Penjaganya middleware `guest` di routes/web.php.
 */
export default function Login() {
    return (
        <>
            <Head title="Masuk" />
            <HalamanLogin />
        </>
    );
}

Login.layout = tanpaShell;
