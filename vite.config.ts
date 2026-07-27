import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        // `fonts: [bunny(...)]` sengaja dilepas: Figtree di-host sendiri lewat
        // @fontsource (lihat resources/css/app.css) supaya panel tetap tampil
        // benar tanpa jaringan keluar.
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        inertia(),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    build: {
        rolldownOptions: {
            output: {
                // React dan recharts hampir tidak pernah berubah antar rilis
                // fitur. Memisahkannya membuat potongan itu tetap tersimpan di
                // cache peramban meski kode aplikasi diperbarui setiap hari.
                advancedChunks: {
                    groups: [
                        {
                            name: 'react',
                            test: /[\\/]node_modules[\\/](react|react-dom|scheduler)[\\/]/,
                        },
                        {
                            name: 'grafik',
                            test: /[\\/]node_modules[\\/](recharts|d3-.*|victory-vendor)[\\/]/,
                        },
                    ],
                },
            },
        },
    },
});
