import { BookOpenIcon } from 'lucide-react';
import { LABEL_KATEGORI_EBOOK } from '@/lib/konstanta';
import { cn } from '@/lib/utils';
import type { KategoriEbook } from '@/types';

/**
 * Sampul ebook. Karena data mock belum punya berkas gambar, tiap kategori
 * mendapat warna tetap — katalog tetap terbaca sebagai katalog, bukan
 * deretan kotak abu-abu yang sama semua.
 */
const WARNA_KATEGORI: Record<KategoriEbook, string> = {
    MINUMAN: 'from-sky-500/25 to-sky-500/5 text-sky-700 dark:text-sky-300',
    MAKANAN_BERAT:
        'from-orange-500/25 to-orange-500/5 text-orange-700 dark:text-orange-300',
    SNACK: 'from-amber-500/25 to-amber-500/5 text-amber-700 dark:text-amber-300',
    DESSERT: 'from-pink-500/25 to-pink-500/5 text-pink-700 dark:text-pink-300',
    BAKERY: 'from-violet-500/25 to-violet-500/5 text-violet-700 dark:text-violet-300',
    BUMBU_SAUS:
        'from-emerald-500/25 to-emerald-500/5 text-emerald-700 dark:text-emerald-300',
};

export function SampulEbook({
    kategori,
    coverUrl,
    judul,
    className,
}: {
    kategori: KategoriEbook;
    coverUrl?: string;
    judul: string;
    className?: string;
}) {
    if (coverUrl) {
        return (
            <img
                src={coverUrl}
                alt={`Sampul ${judul}`}
                className={cn('h-full w-full bg-muted object-cover', className)}
            />
        );
    }

    return (
        <div
            className={cn(
                'flex h-full w-full flex-col items-center justify-center gap-2 bg-gradient-to-br p-4 text-center',
                WARNA_KATEGORI[kategori],
                className,
            )}
        >
            <BookOpenIcon className="size-7 opacity-70" />
            <span className="text-xs font-medium opacity-80">
                {LABEL_KATEGORI_EBOOK[kategori]}
            </span>
        </div>
    );
}
