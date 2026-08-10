import { Head } from '@inertiajs/react';
import { denganShell } from '@/components/layout/denganShell';
import { HalamanDetailTiket } from '@/features/tiket/HalamanDetailTiket';

export default function TiketDetail({ id }: { id: string }) {
    return (
        <>
            <Head title="Detail Tiket" />
            <HalamanDetailTiket id={id} />
        </>
    );
}

TiketDetail.layout = denganShell;
