import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AppShell } from '@/components/layout/AppShell';
import { PenyediaGlobal } from '@/components/layout/PenyediaGlobal';

/**
 * Layout halaman panel: penyedia global + kerangka aplikasi.
 *
 * Dipakai lewat `Halaman.layout = denganShell`. Karena Inertia hanya menukar
 * isi layout — bukan membangun ulang pohonnya — sidebar tidak berkedip dan
 * state ciut/lebarnya bertahan saat pindah halaman.
 */
export const denganShell = (halaman: ReactNode) => (
    <PenyediaGlobal>
        <AppShell>{halaman}</AppShell>
    </PenyediaGlobal>
);

/** Halaman tanpa kerangka panel — saat ini hanya /login. */
export const tanpaShell = (halaman: ReactNode) => (
    <PenyediaGlobal>{halaman}</PenyediaGlobal>
);

/**
 * Varian untuk halaman galat, yang bisa dibuka tamu maupun admin.
 *
 * Menampilkan sidebar kosong kepada orang yang belum masuk hanya membingungkan;
 * halaman galatnya sendiri sudah membawa tautan kembali.
 */
function ShellBilaMasuk({ children }: { children: ReactNode }) {
    const masuk = usePage().props.auth?.admin != null;

    if (masuk) {
        return <AppShell>{children}</AppShell>;
    }

    return (
        <div className="mx-auto w-full max-w-3xl px-5 py-16">{children}</div>
    );
}

export const denganShellBilaMasuk = (halaman: ReactNode) => (
    <PenyediaGlobal>
        <ShellBilaMasuk>{halaman}</ShellBilaMasuk>
    </PenyediaGlobal>
);
