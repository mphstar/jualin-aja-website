import { Head } from '@inertiajs/react';
import { denganShell } from '@/components/layout/denganShell';
import { HalamanDasbor } from '@/features/dasbor/HalamanDasbor';

export default function Dasbor() {
    return (
        <>
            <Head title="Dasbor" />
            <HalamanDasbor />
        </>
    );
}

Dasbor.layout = denganShell;
