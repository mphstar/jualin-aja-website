import { ambil, kirim } from '@/lib/api/client';
import type {
    DurasiPaket,
    Halaman,
    Langganan,
    LanggananRingkas,
    ParamsHalaman,
    StatusLangganan,
} from '@/types';

export interface ParamsLangganan extends ParamsHalaman {
    status?: StatusLangganan | 'SEMUA';
    durasi?: DurasiPaket | 'SEMUA';
    /**
     * Sertakan siklus lama yang sudah digantikan perpanjangan.
     * Default `false`: satu baris per toko (siklus yang sedang berlaku),
     * supaya angka di tab status benar-benar berarti "berapa toko", bukan
     * "berapa baris riwayat".
     */
    termasukRiwayat?: boolean;
}

export function ambilDaftarLangganan(
    params: ParamsLangganan = {},
): Promise<Halaman<LanggananRingkas>> {
    return ambil<Halaman<LanggananRingkas>>('/langganan', { ...params });
}

/** Jumlah per status untuk tab filter (PRD §F4.2). */
export function ambilJumlahPerStatus(
    termasukRiwayat = false,
): Promise<Record<StatusLangganan | 'SEMUA', number>> {
    return ambil<Record<StatusLangganan | 'SEMUA', number>>(
        '/langganan/jumlah-per-status',
        { termasukRiwayat },
    );
}

export function ambilRiwayatLangganan(
    userId: string,
): Promise<LanggananRingkas[]> {
    return ambil<LanggananRingkas[]>(`/langganan/riwayat/${userId}`);
}

/**
 * Perpanjang langganan (PRD §F4.3–F4.5).
 *
 * Aturan tanggal berakhirnya ada di server (App\Support\KondisiLangganan) —
 * satu tempat yang sama dengan yang dipakai pelunasan invoice.
 */
export function perpanjangLangganan(masukan: {
    userId: string;
    durasi: DurasiPaket;
    catatan?: string;
}): Promise<Langganan> {
    return kirim<Langganan>('/langganan/perpanjang', masukan);
}
