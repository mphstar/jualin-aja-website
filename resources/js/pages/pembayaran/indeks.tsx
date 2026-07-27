import { Head } from '@inertiajs/react';
import { denganShell } from '@/components/layout/denganShell';
import { HalamanPembayaran } from '@/features/pembayaran/HalamanPembayaran';

export default function Pembayaran() {
    return (
        <>
            <Head title="Pembayaran" />
            <HalamanPembayaran />
        </>
    );
}

Pembayaran.layout = denganShell;
