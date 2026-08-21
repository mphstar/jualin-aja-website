import { Head } from '@inertiajs/react';
import { denganShell } from '@/components/layout/denganShell';
import { FormEbook } from '@/features/resep/FormEbook';

/** Satu komponen untuk `/resep/baru` dan `/resep/{id}/ubah`; `id` yang membedakan. */
export default function FormResep({ id }: { id?: string }) {
    return (
        <>
            <Head title={id ? 'Ubah Konten' : 'Konten Baru'} />
            <FormEbook id={id} />
        </>
    );
}

FormResep.layout = denganShell;
