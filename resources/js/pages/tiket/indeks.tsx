import { Head } from '@inertiajs/react';
import { denganShell } from '@/components/layout/denganShell';
import { HalamanTiket } from '@/features/tiket/HalamanTiket';

export default function TiketIndeks() {
    return (
        <>
            <Head title="Saran & Komplain" />
            <HalamanTiket />
        </>
    );
}

TiketIndeks.layout = denganShell;
