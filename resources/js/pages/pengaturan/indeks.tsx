import { Head } from '@inertiajs/react';
import { denganShell } from '@/components/layout/denganShell';
import { HalamanPengaturan } from '@/features/pengaturan/HalamanPengaturan';

export default function Pengaturan() {
    return (
        <>
            <Head title="Pengaturan" />
            <HalamanPengaturan />
        </>
    );
}

Pengaturan.layout = denganShell;
