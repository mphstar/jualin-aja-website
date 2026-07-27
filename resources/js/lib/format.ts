import { format, formatDistanceToNowStrict, parseISO } from 'date-fns';
import { id as localeId } from 'date-fns/locale';

const NF = new Intl.NumberFormat('id-ID');

/** 1250000 → "Rp 1.250.000" */
export function formatRupiah(nilai: number): string {
    return `Rp ${NF.format(Math.round(nilai))}`;
}

/** Versi ringkas untuk kartu statistik: 1250000 → "Rp 1,3 jt" */
export function formatRupiahRingkas(nilai: number): string {
    if (Math.abs(nilai) >= 1_000_000_000) {
        return `Rp ${(nilai / 1_000_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} M`;
    }

    if (Math.abs(nilai) >= 1_000_000) {
        return `Rp ${(nilai / 1_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} jt`;
    }

    if (Math.abs(nilai) >= 1_000) {
        return `Rp ${(nilai / 1_000).toLocaleString('id-ID', { maximumFractionDigits: 0 })} rb`;
    }

    return formatRupiah(nilai);
}

/** 1234 → "1.234" */
export function formatAngka(nilai: number): string {
    return NF.format(nilai);
}

/** 12.5 → "+12,5%" · -3 → "−3%" */
export function formatPersen(nilai: number): string {
    const tanda = nilai > 0 ? '+' : nilai < 0 ? '−' : '';
    const angka = Math.abs(nilai).toLocaleString('id-ID', {
        maximumFractionDigits: 1,
    });

    return `${tanda}${angka}%`;
}

/** ISO → "25 Jul 2026" */
export function formatTanggal(iso: string): string {
    return format(parseISO(iso), 'd MMM yyyy', { locale: localeId });
}

/** ISO → "25 Juli 2026" */
export function formatTanggalPanjang(iso: string): string {
    return format(parseISO(iso), 'd MMMM yyyy', { locale: localeId });
}

/** ISO → "25 Jul 2026, 14:30" */
export function formatWaktu(iso: string): string {
    return format(parseISO(iso), 'd MMM yyyy, HH:mm', { locale: localeId });
}

/** ISO → "3 hari lalu" */
export function formatRelatif(iso: string): string {
    const selisihDetik = (Date.now() - parseISO(iso).getTime()) / 1000;

    if (selisihDetik < 60) {
        return 'baru saja';
    }

    return `${formatDistanceToNowStrict(parseISO(iso), { locale: localeId })} lalu`;
}

/**
 * Ubah sisa hari jadi kalimat manusiawi.
 * 23 → "23 hari lagi" · 1 → "Berakhir besok" · 0 → "Berakhir hari ini" · -5 → "Lewat 5 hari"
 */
export function formatSisaHari(hari: number): string {
    if (hari > 1) {
        return `${hari} hari lagi`;
    }

    if (hari === 1) {
        return 'Berakhir besok';
    }

    if (hari === 0) {
        return 'Berakhir hari ini';
    }

    if (hari === -1) {
        return 'Lewat 1 hari';
    }

    return `Lewat ${Math.abs(hari)} hari`;
}

/** 13002342 → "12,4 MB" */
export function formatUkuranFile(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    const kb = bytes / 1024;

    if (kb < 1024) {
        return `${kb.toLocaleString('id-ID', { maximumFractionDigits: 0 })} KB`;
    }

    const mb = kb / 1024;

    if (mb < 1024) {
        return `${mb.toLocaleString('id-ID', { maximumFractionDigits: 1 })} MB`;
    }

    return `${(mb / 1024).toLocaleString('id-ID', { maximumFractionDigits: 2 })} GB`;
}

/** "Budi Santoso" → "BS" — untuk fallback avatar. */
export function inisial(nama: string): string {
    return nama
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((kata) => kata[0]?.toUpperCase() ?? '')
        .join('');
}
