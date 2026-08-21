import { Head } from '@inertiajs/react';
import { denganShell } from '@/components/layout/denganShell';
import { HalamanKatalog } from '@/features/resep/HalamanKatalog';

export default function Resep() {
    return (
        <>
            <Head title="Pustaka" />
            <HalamanKatalog />
        </>
    );
}

Resep.layout = denganShell;
