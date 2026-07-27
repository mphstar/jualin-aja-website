import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

type Nada = 'netral' | 'sukses' | 'peringatan' | 'bahaya' | 'info';

const GAYA: Record<Nada, string> = {
    netral: 'bg-muted text-muted-foreground',
    sukses: 'bg-sukses-lembut text-sukses',
    peringatan: 'bg-peringatan-lembut text-peringatan',
    bahaya: 'bg-bahaya-lembut text-destructive',
    info: 'bg-info-lembut text-info',
};

const UKURAN = {
    sm: { kotak: 'size-8 rounded-md', ikon: 'size-4' },
    md: { kotak: 'size-10 rounded-lg', ikon: 'size-[1.125rem]' },
} as const;

/**
 * Ikon garis di dalam kotak lembut — motif pengikat seluruh panel.
 *
 * Dipakai di kepala kartu, kartu statistik, dan baris daftar. Karena
 * bentuknya sama di mana pun, halaman yang isinya berbeda-beda tetap
 * terbaca sebagai satu keluarga tanpa perlu warna tambahan.
 */
export function IkonKotak({
    ikon: Ikon,
    nada = 'netral',
    ukuran = 'md',
    className,
}: {
    ikon: LucideIcon;
    nada?: Nada;
    ukuran?: keyof typeof UKURAN;
    className?: string;
}) {
    const { kotak, ikon } = UKURAN[ukuran];

    return (
        <div
            aria-hidden
            className={cn(
                'flex shrink-0 items-center justify-center',
                kotak,
                GAYA[nada],
                className,
            )}
        >
            <Ikon className={ikon} />
        </div>
    );
}
