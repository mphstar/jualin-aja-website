import { Head } from '@inertiajs/react';
import { denganShell } from '@/components/layout/denganShell';
import { HalamanPengguna } from '@/features/pengguna/HalamanPengguna';

export default function Pengguna() {
    return (
        <>
            <Head title="Pengguna" />
            <HalamanPengguna />
        </>
    );
}

Pengguna.layout = denganShell;
