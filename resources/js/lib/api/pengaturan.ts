import { ambil, ganti } from '@/lib/api/client';
import type { DurasiPaket } from '@/types';

export type HargaPaket = Record<DurasiPaket, number>;

export interface PengaturanMayar {
    api_key: string;
    webhook_secret?: string;
    is_production: boolean;
    timeout: number;
}

export function ambilHargaPaket(): Promise<HargaPaket> {
    return ambil<HargaPaket>('/pengaturan/harga-paket');
}

/**
 * Menyimpan harga paket.
 *
 * Harga yang sudah tercetak di invoice lama TIDAK ikut berubah — nominal
 * pembayaran disimpan per transaksi, jadi perubahan di sini hanya berlaku
 * untuk perpanjangan berikutnya.
 *
 * Selisihnya dicatat ke log oleh server, yang memang memegang nilai lamanya.
 */
export function simpanHargaPaket(harga: HargaPaket): Promise<HargaPaket> {
    return ganti<HargaPaket>('/pengaturan/harga-paket', harga);
}

export function ambilPengaturanMayar(): Promise<PengaturanMayar> {
    return ambil<PengaturanMayar>('/pengaturan/mayar');
}

/**
 * Menyimpan konfigurasi Mayar.
 *
 * Nilai kosong pada API key mengartikan pembayaran otomatis mati (server
 * menolak pembuatan tagihan dengan pesan yang mengarahkan ke perpanjangan
 * manual). Perubahan mode dicatat ke log oleh server.
 */
export function simpanPengaturanMayar(
    mayar: PengaturanMayar,
): Promise<PengaturanMayar> {
    return ganti<PengaturanMayar>('/pengaturan/mayar', mayar);
}
