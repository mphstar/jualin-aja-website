import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { EyeIcon, EyeOffIcon, Loader2Icon, LogInIcon } from 'lucide-react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import * as z from 'zod';
import { Button } from '@/components/ui/button';
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { PanelMerek } from '@/features/auth/PanelMerek';
import { useAuthStore } from '@/stores/authStore';

const skema = z.object({
    email: z.email({ error: 'Format email tidak valid.' }),
    kataSandi: z.string().min(1, { error: 'Kata sandi wajib diisi.' }),
});

type NilaiForm = z.infer<typeof skema>;

/**
 * Komposisi dua panel: panel merek gelap di kiri, kartu formulir di kanan,
 * di atas kanvas hangat.
 *
 * Kartu formulir sengaja diberi jarak `m-2` terhadap wadahnya, dan wadahnya
 * berwarna pekat, supaya latar gelap itu masih terlihat di sudut-sudut lengkung
 * kartu. Tanpa itu kedua panel cuma berimpit di satu garis dan efek "kartu yang
 * menimpa" dari referensi hilang.
 */
export function HalamanLogin() {
    const masuk = useAuthStore((s) => s.masuk);
    const memuat = useAuthStore((s) => s.memuat);
    const error = useAuthStore((s) => s.error);
    const [lihatSandi, setLihatSandi] = useState(false);

    const form = useForm<NilaiForm>({
        resolver: zodResolver(skema),
        defaultValues: { email: '', kataSandi: '' },
        mode: 'onTouched',
    });

    /**
     * Tidak ada penjaga "sudah masuk" di sini: middleware `guest` Laravel yang
     * memegangnya (routes/web.php). Halaman ini bahkan tidak pernah sampai ke
     * peramban bila sesinya masih hidup.
     *
     * Tujuan setelah masuk juga datang dari server — dialah yang menyimpan
     * halaman apa yang tadi hendak dibuka sebelum tamu dilempar ke sini.
     */
    async function kirim(nilai: NilaiForm) {
        const tujuan = await masuk(nilai.email, nilai.kataSandi);

        if (tujuan) {
            router.visit(tujuan, { replace: true });
        }
    }

    return (
        <div className="min-h-svh bg-kanvas p-2 sm:p-3">
            <div className="grid min-h-[calc(100svh-1rem)] overflow-hidden rounded-[1.75rem] bg-background sm:min-h-[calc(100svh-1.5rem)] lg:grid-cols-2 lg:bg-pekat">
                <PanelMerek />

                <div className="flex flex-col bg-background px-6 py-8 sm:px-10 lg:m-2 lg:ml-0 lg:rounded-[1.5rem] lg:px-12 lg:py-10">
                    <header>
                        <Merek />
                    </header>

                    <main className="flex flex-1 flex-col justify-center py-10">
                        <div className="w-full max-w-[26rem]">
                            <h1 className="text-[2rem] leading-none font-medium tracking-[-0.03em]">
                                Masuk
                            </h1>
                            <p className="mt-3 text-sm text-muted-foreground">
                                Gunakan akun admin untuk melanjutkan.
                            </p>

                            <Form {...form}>
                                <form
                                    onSubmit={form.handleSubmit(kirim)}
                                    className="mt-8 grid gap-3"
                                    noValidate
                                >
                                    <FormField
                                        control={form.control}
                                        name="email"
                                        render={({ field }) => (
                                            <FormItem className="gap-1.5">
                                                <FormLabel className="sr-only">
                                                    Email
                                                </FormLabel>
                                                <FormControl>
                                                    <Input
                                                        type="email"
                                                        autoComplete="username"
                                                        placeholder="Email"
                                                        className="h-12 rounded-full px-5 shadow-none"
                                                        {...field}
                                                    />
                                                </FormControl>
                                                <FormMessage className="px-5" />
                                            </FormItem>
                                        )}
                                    />

                                    <FormField
                                        control={form.control}
                                        name="kataSandi"
                                        render={({ field }) => (
                                            <FormItem className="gap-1.5">
                                                <FormLabel className="sr-only">
                                                    Kata sandi
                                                </FormLabel>
                                                <FormControl>
                                                    <div className="relative">
                                                        <Input
                                                            type={
                                                                lihatSandi
                                                                    ? 'text'
                                                                    : 'password'
                                                            }
                                                            autoComplete="current-password"
                                                            placeholder="Kata sandi"
                                                            className="h-12 rounded-full px-5 pr-12 shadow-none"
                                                            {...field}
                                                        />
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            className="absolute top-0 right-0 h-full rounded-full px-4 hover:bg-transparent"
                                                            onClick={() =>
                                                                setLihatSandi(
                                                                    (v) => !v,
                                                                )
                                                            }
                                                            aria-label={
                                                                lihatSandi
                                                                    ? 'Sembunyikan kata sandi'
                                                                    : 'Tampilkan kata sandi'
                                                            }
                                                        >
                                                            {lihatSandi ? (
                                                                <EyeOffIcon className="size-4 text-muted-foreground" />
                                                            ) : (
                                                                <EyeIcon className="size-4 text-muted-foreground" />
                                                            )}
                                                        </Button>
                                                    </div>
                                                </FormControl>
                                                <FormMessage className="px-5" />
                                            </FormItem>
                                        )}
                                    />

                                    {error && (
                                        <p
                                            className="px-5 text-sm text-destructive"
                                            role="alert"
                                        >
                                            {error}
                                        </p>
                                    )}

                                    <Button
                                        type="submit"
                                        className="tombol-gradien mt-3 h-12 w-full rounded-full border-0 text-[0.95rem] text-white shadow-none hover:opacity-95 focus-visible:ring-oranye/40"
                                        disabled={memuat}
                                    >
                                        {memuat ? (
                                            <Loader2Icon className="size-4 animate-spin" />
                                        ) : (
                                            <LogInIcon className="size-4" />
                                        )}
                                        {memuat ? 'Memproses…' : 'Masuk'}
                                    </Button>
                                </form>
                            </Form>
                        </div>
                    </main>

                    <footer className="flex flex-col gap-1.5 text-[0.7rem] text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                        <p>
                            © {new Date().getFullYear()} JualinAja · Resep &
                            Kasir UMKM
                        </p>
                        <p>Panel pengelola platform</p>
                    </footer>
                </div>
            </div>
        </div>
    );
}

/**
 * Kunci merek untuk header kartu: ikon dari logo, wordmark sebagai teks.
 *
 * Wordmark-nya ditulis sebagai teks, bukan memakai `logo.png` apa adanya,
 * karena lockup di berkas itu vertikal (ikon di atas, tagline di bawah) dan
 * pada tinggi 36px barisnya sudah tidak terbaca. Dua warna teksnya disalin dari
 * logo: hijau tua untuk "Jualin", oranye untuk "Aja" — tapi hanya di tema
 * terang, sebab hijau tuanya hilang di atas kartu gelap.
 *
 * Ikonnya berkas terpisah, `images/logo-mark.png` — potongan kotak ikon dari
 * `logo.png` (239,228–791,608) supaya bisa disejajarkan mendatar dengan
 * wordmark.
 */
function Merek() {
    return (
        <div className="flex items-center gap-2.5">
            <span className="grid h-9 w-[3.3rem] shrink-0 place-items-center overflow-hidden rounded-lg bg-white">
                <img
                    src="/images/logo-mark.png"
                    alt=""
                    className="size-full object-contain"
                />
            </span>
            <span className="text-[1.35rem] leading-none font-bold tracking-[-0.02em] text-hijau dark:text-foreground">
                Jualin<span className="text-oranye">Aja</span>
            </span>
        </div>
    );
}
