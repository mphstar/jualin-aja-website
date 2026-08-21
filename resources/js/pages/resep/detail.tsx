import { Head } from '@inertiajs/react';
import { denganShell } from '@/components/layout/denganShell';
import { HalamanDetailEbook } from '@/features/resep/HalamanDetailEbook';

export default function DetailResep({ id }: { id: string }) {
    return (
        <>
            <Head title="Detail Konten" />
            <HalamanDetailEbook id={id} />
        </>
    );
}

DetailResep.layout = denganShell;
