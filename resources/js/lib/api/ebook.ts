import { ambil, hapus, kirim, keFormData, tambal } from '@/lib/api/client';
import type {
    Ebook,
    Halaman,
    JenisKonten,
    KategoriEbook,
    KategoriPrompt,
    ParamsHalaman,
    StatusEbook,
    TitikDeret,
    UnduhanRingkas,
} from '@/types';

export interface ParamsEbook extends ParamsHalaman {
    jenis?: JenisKonten | 'SEMUA';
    kategori?: KategoriEbook | 'SEMUA';
    kategoriPrompt?: KategoriPrompt | 'SEMUA';
    status?: StatusEbook | 'SEMUA';
}

/**
 * Berkas dikirim sebagai `File` lewat multipart, bukan sebagai object URL.
 * Bedanya nyata: unggahan sekarang benar-benar tersimpan dan tetap ada setelah
 * halaman dimuat ulang.
 */
export interface MasukanEbook {
    jenis: JenisKonten;
    judul: string;
    kategori?: KategoriEbook;
    kategoriPrompt?: KategoriPrompt;
    deskripsi: string;
    status: StatusEbook;
    jumlahHalaman?: number;
    cover?: File;
    berkas?: File;
}

export interface DetailEbook {
    ebook: Ebook;
    unduhan: UnduhanRingkas[];
    deret30Hari: TitikDeret[];
}

export function ambilDaftarEbook(
    params: ParamsEbook = {},
): Promise<Halaman<Ebook>> {
    return ambil<Halaman<Ebook>>('/ebook', { ...params });
}

export function ambilDetailEbook(id: string): Promise<DetailEbook> {
    return ambil<DetailEbook>(`/ebook/${id}`);
}

export function tambahEbook(masukan: MasukanEbook): Promise<Ebook> {
    return kirim<Ebook>('/ebook', keFormData({ ...masukan }));
}

/**
 * POST, bukan PUT: badan multipart tidak terbaca PHP pada PUT, jadi unggahan
 * cover & PDF harus lewat POST (lihat routes/api.php).
 */
export function ubahEbook(id: string, masukan: MasukanEbook): Promise<Ebook> {
    return kirim<Ebook>(`/ebook/${id}`, keFormData({ ...masukan }));
}

export function ubahStatusEbook(
    id: string,
    status: StatusEbook,
): Promise<Ebook> {
    return tambal<Ebook>(`/ebook/${id}/status`, { status });
}

export function hapusEbook(id: string): Promise<void> {
    return hapus(`/ebook/${id}`);
}
