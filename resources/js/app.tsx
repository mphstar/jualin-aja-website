import { createInertiaApp } from '@inertiajs/react';

const namaAplikasi = import.meta.env.VITE_APP_NAME || 'Jualin Aja';

/*
 * Tanpa `setup()` sendiri — pemasangan (dan hidrasi SSR) diurus Inertia.
 * Penyedia global seperti tema, tooltip, dan toast dipasang lewat layout
 * halaman; lihat resources/js/components/layout/PenyediaGlobal.tsx.
 */
createInertiaApp({
    title: (judul) => (judul ? `${judul} · ${namaAplikasi}` : namaAplikasi),
    progress: { color: '#4B5563' },
});
