import { ambil, kirim, tambal } from '@/lib/api/client';
import type { AdminUser } from '@/types';

export interface MasukanMasuk {
    email: string;
    kataSandi: string;
}

export interface HasilMasuk {
    admin: AdminUser;
    /**
     * Halaman yang tadi hendak dibuka sebelum tamu dilempar ke /login.
     * Ditentukan server — kliennya tidak menyimpan riwayat itu.
     */
    tujuan: string;
}

export function masuk(masukan: MasukanMasuk): Promise<HasilMasuk> {
    return kirim<HasilMasuk>('/auth/login', masukan);
}

export function keluar(): Promise<void> {
    return kirim<void>('/auth/logout');
}

export function ambilProfil(): Promise<AdminUser> {
    return ambil<AdminUser>('/auth/saya');
}

export function ubahProfil(
    patch: Partial<Pick<AdminUser, 'nama' | 'email' | 'avatarUrl'>>,
): Promise<AdminUser> {
    return tambal<AdminUser>('/auth/profil', patch);
}
