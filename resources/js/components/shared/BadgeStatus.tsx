import { formatSisaHari } from '@/lib/format';
import {
    LABEL_STATUS_EBOOK,
    LABEL_STATUS_LANGGANAN,
    LABEL_STATUS_PEMBAYARAN,
} from '@/lib/konstanta';
import { AMBANG_AKAN_BERAKHIR } from '@/lib/konstanta';
import { cn } from '@/lib/utils';
import type { StatusEbook, StatusLangganan, StatusPembayaran } from '@/types';

/**
 * Pola warna badge: latar lembut + teks pekat, memakai token yang
 * punya nilai terang DAN gelap — jadi otomatis benar di kedua tema
 * tanpa satu pun `dark:` di sini.
 */
const DASAR =
    'inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-xs font-medium whitespace-nowrap';

const GAYA = {
    sukses: 'bg-sukses-lembut text-sukses border-sukses/25',
    peringatan: 'bg-peringatan-lembut text-peringatan border-peringatan/25',
    bahaya: 'bg-bahaya-lembut text-destructive border-destructive/25',
    info: 'bg-info-lembut text-info border-info/25',
    netral: 'bg-muted text-muted-foreground border-border',
} as const;

type Nada = keyof typeof GAYA;

function Titik({ nada }: { nada: Nada }) {
    const warna: Record<Nada, string> = {
        sukses: 'bg-sukses',
        peringatan: 'bg-peringatan',
        bahaya: 'bg-destructive',
        info: 'bg-info',
        netral: 'bg-muted-foreground',
    };

    return <span className={cn('size-1.5 rounded-full', warna[nada])} />;
}

// ------------------------------------------------------------
// Status langganan
// ------------------------------------------------------------

const NADA_LANGGANAN: Record<StatusLangganan, Nada> = {
    AKTIF: 'sukses',
    AKAN_BERAKHIR: 'peringatan',
    KEDALUWARSA: 'bahaya',
    TRIAL: 'info',
    NONAKTIF: 'netral',
};

export function BadgeStatusLangganan({
    status,
    className,
}: {
    status: StatusLangganan;
    className?: string;
}) {
    const nada = NADA_LANGGANAN[status];

    return (
        <span className={cn(DASAR, GAYA[nada], className)}>
            <Titik nada={nada} />
            {LABEL_STATUS_LANGGANAN[status]}
        </span>
    );
}

// ------------------------------------------------------------
// Status pembayaran
// ------------------------------------------------------------

const NADA_PEMBAYARAN: Record<StatusPembayaran, Nada> = {
    LUNAS: 'sukses',
    MENUNGGU: 'peringatan',
    GAGAL: 'bahaya',
    REFUND: 'netral',
};

export function BadgeStatusPembayaran({
    status,
    className,
}: {
    status: StatusPembayaran;
    className?: string;
}) {
    const nada = NADA_PEMBAYARAN[status];

    return (
        <span className={cn(DASAR, GAYA[nada], className)}>
            <Titik nada={nada} />
            {LABEL_STATUS_PEMBAYARAN[status]}
        </span>
    );
}

// ------------------------------------------------------------
// Status ebook
// ------------------------------------------------------------

export function BadgeStatusEbook({
    status,
    className,
}: {
    status: StatusEbook;
    className?: string;
}) {
    const nada: Nada = status === 'TERBIT' ? 'sukses' : 'netral';

    return (
        <span className={cn(DASAR, GAYA[nada], className)}>
            <Titik nada={nada} />
            {LABEL_STATUS_EBOOK[status]}
        </span>
    );
}

// ------------------------------------------------------------
// Sisa hari — warnanya ikut tingkat urgensi
// ------------------------------------------------------------

export function BadgeSisaHari({
    hari,
    nonaktif,
    className,
}: {
    hari: number;
    nonaktif?: boolean;
    className?: string;
}) {
    if (nonaktif) {
        return (
            <span className={cn(DASAR, GAYA.netral, className)}>
                Ditangguhkan
            </span>
        );
    }

    const nada: Nada =
        hari < 0
            ? 'bahaya'
            : hari <= AMBANG_AKAN_BERAKHIR
              ? 'peringatan'
              : 'sukses';

    return (
        <span className={cn(DASAR, GAYA[nada], 'angka-tabular', className)}>
            {formatSisaHari(hari)}
        </span>
    );
}
