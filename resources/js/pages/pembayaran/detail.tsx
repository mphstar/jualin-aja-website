import { Head } from '@inertiajs/react';
import { denganShell } from '@/components/layout/denganShell';
import { HalamanDetailInvoice } from '@/features/pembayaran/HalamanDetailInvoice';

export default function DetailPembayaran({ id }: { id: string }) {
    return (
        <>
            <Head title="Detail Invoice" />
            <HalamanDetailInvoice id={id} />
        </>
    );
}

DetailPembayaran.layout = denganShell;
