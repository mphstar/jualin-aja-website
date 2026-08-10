import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { ChefHatIcon, EyeIcon, EyeOffIcon, Loader2Icon } from 'lucide-react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import * as z from 'zod';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { KREDENSIAL_DEMO } from '@/lib/konstanta';
import { useAuthStore } from '@/stores/authStore';

const skema = z.object({
    email: z.email({ error: 'Format email tidak valid.' }),
    kataSandi: z.string().min(1, { error: 'Kata sandi wajib diisi.' }),
});

type NilaiForm = z.infer<typeof skema>;

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
        <div className="flex min-h-svh items-center justify-center p-4">
            <div className="w-full max-w-sm">
                <div className="mb-6 flex flex-col items-center gap-3 text-center">
                    <img
                        src="/logo.png"
                        alt="JualinAja Logo"
                        className="h-24 w-auto object-contain drop-shadow-sm"
                    />
                    <p className="text-sm font-medium text-muted-foreground">
                        Panel Kelola & Monitoring POS JualinAja
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Masuk</CardTitle>
                        <CardDescription>
                            Gunakan akun admin untuk melanjutkan.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form {...form}>
                            <form
                                onSubmit={form.handleSubmit(kirim)}
                                className="grid gap-4"
                                noValidate
                            >
                                <FormField
                                    control={form.control}
                                    name="email"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Email</FormLabel>
                                            <FormControl>
                                                <Input
                                                    type="email"
                                                    autoComplete="username"
                                                    placeholder={
                                                        KREDENSIAL_DEMO.email
                                                    }
                                                    {...field}
                                                />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="kataSandi"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Kata Sandi</FormLabel>
                                            <FormControl>
                                                <div className="relative">
                                                    <Input
                                                        type={
                                                            lihatSandi
                                                                ? 'text'
                                                                : 'password'
                                                        }
                                                        autoComplete="current-password"
                                                        className="pr-10"
                                                        {...field}
                                                    />
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        className="absolute top-0 right-0 h-full px-3 hover:bg-transparent"
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
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                {error && (
                                    <p
                                        className="text-sm text-destructive"
                                        role="alert"
                                    >
                                        {error}
                                    </p>
                                )}

                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={memuat}
                                >
                                    {memuat && (
                                        <Loader2Icon className="size-4 animate-spin" />
                                    )}
                                    {memuat ? 'Memproses…' : 'Masuk'}
                                </Button>
                            </form>
                        </Form>
                    </CardContent>
                </Card>

                {/* Ini demo frontend-only, jadi kredensialnya memang ditampilkan. */}
                <div className="mt-4 rounded-lg border bg-muted/50 p-3 text-xs text-muted-foreground">
                    <p className="mb-1 font-medium text-foreground">
                        Akun demo
                    </p>
                    <p>
                        Email:{' '}
                        <code className="font-mono">
                            {KREDENSIAL_DEMO.email}
                        </code>
                    </p>
                    <p>
                        Kata sandi:{' '}
                        <code className="font-mono">
                            {KREDENSIAL_DEMO.kataSandi}
                        </code>
                    </p>
                </div>
            </div>
        </div>
    );
}
