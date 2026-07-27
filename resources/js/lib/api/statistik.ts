import { ambil } from '@/lib/api/client';
import type { IrisanPaket, StatistikDasbor, TitikDeret } from '@/types';

export function ambilStatistikDasbor(): Promise<StatistikDasbor> {
    return ambil<StatistikDasbor>('/statistik/dasbor');
}

/** 12 bulan terakhir — jumlah pendaftaran toko baru per bulan. */
export function ambilTrenPendaftaran(): Promise<TitikDeret[]> {
    return ambil<TitikDeret[]>('/statistik/tren-pendaftaran');
}

/** 12 bulan terakhir — total pembayaran lunas per bulan (rupiah). */
export function ambilTrenPendapatan(): Promise<TitikDeret[]> {
    return ambil<TitikDeret[]>('/statistik/tren-pendapatan');
}

/** Komposisi durasi paket yang sedang dipakai tiap toko. */
export function ambilKomposisiPaket(): Promise<IrisanPaket[]> {
    return ambil<IrisanPaket[]>('/statistik/komposisi-paket');
}
