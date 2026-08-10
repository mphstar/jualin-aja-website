import {
    ActivityIcon,
    BookOpenIcon,
    CreditCardIcon,
    LayoutDashboardIcon,
    MessageSquareIcon,
    SettingsIcon,
    TicketIcon,
    UsersIcon,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

export interface ItemNavigasi {
    judul: string;
    href: string;
    ikon: LucideIcon;
    /** Dipakai judul bar mobile & <title> halaman. */
    keterangan: string;
}

export interface GrupNavigasi {
    label: string;
    item: ItemNavigasi[];
}

/**
 * Navigasi dikelompokkan menurut apa yang sedang dikerjakan admin, bukan
 * menurut nama modul: memantau kondisi, mengelola isi, lalu urusan sistem.
 * Satu daftar rata sepanjang tujuh baris tidak memberi titik istirahat.
 */
export const GRUP_NAVIGASI: GrupNavigasi[] = [
    {
        label: 'Pantau',
        item: [
            {
                judul: 'Dasbor',
                href: '/dasbor',
                ikon: LayoutDashboardIcon,
                keterangan: 'Ringkasan kondisi langganan dan pendapatan',
            },
            {
                judul: 'Pengguna',
                href: '/pengguna',
                ikon: UsersIcon,
                keterangan: 'Daftar pemilik toko yang memakai aplikasi POS',
            },
            {
                judul: 'Langganan',
                href: '/langganan',
                ikon: TicketIcon,
                keterangan: 'Pantau masa aktif dan perpanjang langganan',
            },
        ],
    },
    {
        label: 'Kelola',
        item: [
            {
                judul: 'Resep',
                href: '/resep',
                ikon: BookOpenIcon,
                keterangan: 'Katalog ebook resep untuk pelanggan berlangganan',
            },
            {
                judul: 'Pembayaran',
                href: '/pembayaran',
                ikon: CreditCardIcon,
                keterangan: 'Riwayat pembayaran langganan dan invoice',
            },
            {
                judul: 'Saran & Komplain',
                href: '/tiket',
                ikon: MessageSquareIcon,
                keterangan: 'Laporan masukan dan masalah pengguna POS',
            },
        ],
    },
    {
        label: 'Sistem',
        item: [
            {
                judul: 'Aktivitas',
                href: '/aktivitas',
                ikon: ActivityIcon,
                keterangan: 'Catatan seluruh tindakan yang dilakukan admin',
            },
            {
                judul: 'Pengaturan',
                href: '/pengaturan',
                ikon: SettingsIcon,
                keterangan: 'Profil admin, tampilan, dan harga paket',
            },
        ],
    },
];

/** Seluruh item, tanpa pengelompokan — untuk pencarian path. */
export const NAVIGASI: ItemNavigasi[] = GRUP_NAVIGASI.flatMap((g) => g.item);

/** Cari item navigasi yang cocok dengan sebuah path. */
export function cariNavigasi(pathname: string): ItemNavigasi | undefined {
    return NAVIGASI.find(
        (item) =>
            pathname === item.href || pathname.startsWith(`${item.href}/`),
    );
}
