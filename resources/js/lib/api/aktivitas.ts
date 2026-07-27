import { ambil } from '@/lib/api/client';
import type { Halaman, JenisAksi, LogAktivitas, ParamsHalaman } from '@/types';

export interface ParamsAktivitas extends ParamsHalaman {
    aksi?: JenisAksi | 'SEMUA';
    dari?: string;
    sampai?: string;
}

/**
 * Log HANYA dibaca dari sini. Penulisannya ada di server
 * (App\Services\PencatatAktivitas), dipanggil dari tiap Action — bukan dari
 * komponen. Kalau UI yang mencatat, satu jalur yang terlewat membuat seluruh
 * jejaknya bohong (PRD §F7.5).
 */
export function ambilAktivitas(
    params: ParamsAktivitas = {},
): Promise<Halaman<LogAktivitas>> {
    return ambil<Halaman<LogAktivitas>>('/aktivitas', { ...params });
}

/** 8 aktivitas terbaru untuk feed dasbor. */
export function ambilAktivitasTerbaru(batas = 8): Promise<LogAktivitas[]> {
    return ambil<LogAktivitas[]>('/aktivitas/terbaru', { batas });
}
