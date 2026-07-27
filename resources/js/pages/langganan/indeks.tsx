import { Head } from '@inertiajs/react';
import { denganShell } from '@/components/layout/denganShell';
import { HalamanLangganan } from '@/features/langganan/HalamanLangganan';

export default function Langganan() {
    return (
        <>
            <Head title="Langganan" />
            <HalamanLangganan />
        </>
    );
}

Langganan.layout = denganShell;
