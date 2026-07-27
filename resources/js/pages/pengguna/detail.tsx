import { Head } from '@inertiajs/react';
import { denganShell } from '@/components/layout/denganShell';
import { HalamanDetailPengguna } from '@/features/pengguna/HalamanDetailPengguna';

/** `id` datang dari parameter rute (routes/web.php), bukan dibaca dari URL. */
export default function DetailPengguna({ id }: { id: string }) {
    return (
        <>
            <Head title="Detail Pengguna" />
            <HalamanDetailPengguna id={id} />
        </>
    );
}

DetailPengguna.layout = denganShell;
