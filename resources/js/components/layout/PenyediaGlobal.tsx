import { useEffect } from 'react';
import type { ReactNode } from 'react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { tentukanGelap, useTemaStore } from '@/stores/temaStore';

/**
 * Satu-satunya tempat yang menyentuh <html class="dark">.
 *
 * Komponen lain cukup mengubah store — menoggle kelas langsung dari komponen
 * membuat tema punya dua sumber kebenaran yang saling berebut. Kedipan saat
 * muat pertama dicegah skrip inline di resources/views/app.blade.php.
 */
function EfekTema() {
    const tema = useTemaStore((s) => s.tema);

    useEffect(() => {
        function terapkan() {
            document.documentElement.classList.toggle(
                'dark',
                tentukanGelap(tema),
            );
        }

        terapkan();

        // Ikuti perubahan preferensi OS saat mode "sistem" dipilih.
        if (tema !== 'sistem') {
            return;
        }

        const media = window.matchMedia('(prefers-color-scheme: dark)');
        media.addEventListener('change', terapkan);

        return () => media.removeEventListener('change', terapkan);
    }, [tema]);

    return null;
}

/**
 * Penyedia yang harus membungkus SETIAP halaman: tema, tooltip, dan toast.
 *
 * Dipasang lewat layout Inertia, bukan lewat `setup()` di app.tsx. Alasannya
 * konkret: `setup()` yang memanggil `createRoot` sendiri akan me-mount ulang
 * seluruh aplikasi setiap kali Vite mengirim pembaruan panas, dan React
 * berakhir dengan dua pohon yang berebut satu elemen — halaman masih terlihat
 * benar, tapi tidak ada satu pun tombolnya yang merespons.
 */
export function PenyediaGlobal({ children }: { children: ReactNode }) {
    return (
        <TooltipProvider delayDuration={300}>
            <EfekTema />
            {children}
            <Toaster />
        </TooltipProvider>
    );
}
