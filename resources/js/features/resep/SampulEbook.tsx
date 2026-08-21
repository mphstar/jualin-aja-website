import { BookOpenIcon, SparklesIcon } from 'lucide-react';
import {
    LABEL_KATEGORI_EBOOK,
    LABEL_KATEGORI_PROMPT,
} from '@/lib/konstanta';
import { cn } from '@/lib/utils';
import type { JenisKonten, KategoriEbook, KategoriPrompt } from '@/types';

/**
 * Sampul konten pustaka. Karena data belum tentu punya berkas gambar, tiap
 * kategori mendapat warna tetap — katalog tetap terbaca sebagai katalog,
 * bukan deretan kotak abu-abu yang sama semua.
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

const WARNA_KATEGORI_PROMPT: Record<KategoriPrompt, string> = {
    LOGO: 'from-indigo-500/25 to-indigo-500/5 text-indigo-700 dark:text-indigo-300',
    DESAIN_MENU:
        'from-teal-500/25 to-teal-500/5 text-teal-700 dark:text-teal-300',
    POSTER_PROMOSI:
        'from-rose-500/25 to-rose-500/5 text-rose-700 dark:text-rose-300',
    SOSIAL_MEDIA:
        'from-cyan-500/25 to-cyan-500/5 text-cyan-700 dark:text-cyan-300',
    FOTO_PRODUK:
        'from-lime-500/25 to-lime-500/5 text-lime-700 dark:text-lime-300',
    KEMASAN_PRODUK:
        'from-fuchsia-500/25 to-fuchsia-500/5 text-fuchsia-700 dark:text-fuchsia-300',
};

export function SampulEbook({
    jenis,
    kategori,
    kategoriPrompt,
    coverUrl,
    judul,
    className,
}: {
    jenis: JenisKonten;
    kategori?: KategoriEbook;
    kategoriPrompt?: KategoriPrompt;
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

    const prompt = jenis === 'PROMPT';
    const label = prompt
        ? kategoriPrompt
            ? LABEL_KATEGORI_PROMPT[kategoriPrompt]
            : 'Prompt'
        : kategori
          ? LABEL_KATEGORI_EBOOK[kategori]
          : 'Resep';
    const warna = prompt
        ? kategoriPrompt
            ? WARNA_KATEGORI_PROMPT[kategoriPrompt]
            : 'from-slate-500/25 to-slate-500/5 text-slate-700 dark:text-slate-300'
        : kategori
          ? WARNA_KATEGORI[kategori]
          : 'from-slate-500/25 to-slate-500/5 text-slate-700 dark:text-slate-300';

    return (
        <div
            className={cn(
                'flex h-full w-full flex-col items-center justify-center gap-2 bg-gradient-to-br p-4 text-center',
                warna,
                className,
            )}
        >
            {prompt ? (
                <SparklesIcon className="size-7 opacity-70" />
            ) : (
                <BookOpenIcon className="size-7 opacity-70" />
            )}
            <span className="text-xs font-medium opacity-80">{label}</span>
        </div>
    );
}
