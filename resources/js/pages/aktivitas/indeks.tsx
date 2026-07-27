import { Head } from '@inertiajs/react';
import { denganShell } from '@/components/layout/denganShell';
import { HalamanAktivitas } from '@/features/aktivitas/HalamanAktivitas';

export default function Aktivitas() {
    return (
        <>
            <Head title="Aktivitas" />
            <HalamanAktivitas />
        </>
    );
}

Aktivitas.layout = denganShell;
