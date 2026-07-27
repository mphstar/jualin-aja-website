import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { Loader2Icon } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { toast } from 'sonner';
import * as z from 'zod';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { useAdmin } from '@/hooks/useAdmin';
import { api, KesalahanApi } from '@/lib/api';
import { formatWaktu, inisial } from '@/lib/format';

const skema = z.object({
    nama: z.string().min(3, { error: 'Nama minimal 3 karakter.' }),
    email: z.email({ error: 'Format email tidak valid.' }),
});

type NilaiForm = z.infer<typeof skema>;

export function FormProfil() {
    const admin = useAdmin();

    const form = useForm<NilaiForm>({
        resolver: zodResolver(skema),
        defaultValues: { nama: admin?.nama ?? '', email: admin?.email ?? '' },
        mode: 'onTouched',
    });

    const namaSekarang = form.watch('nama');

    async function kirim(nilai: NilaiForm) {
        try {
            await api.auth.ubahProfil(nilai);
            // Muat ulang shared prop `auth` saja: nama di sidebar ikut berubah
            // tanpa merender ulang seluruh halaman.
            router.reload({ only: ['auth'] });
            toast.success('Profil disimpan');
            form.reset(nilai);
        } catch (e) {
            toast.error(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Gagal menyimpan profil.',
            );
        }
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Profil admin</CardTitle>
                <p className="text-sm text-muted-foreground">
                    Nama ini yang tercatat sebagai pelaku di log aktivitas.
                </p>
            </CardHeader>

            <CardContent>
                <Form {...form}>
                    <form
                        onSubmit={form.handleSubmit(kirim)}
                        className="grid gap-4"
                        noValidate
                    >
                        <div className="flex items-center gap-4">
                            <Avatar className="size-14">
                                <AvatarFallback className="text-lg">
                                    {inisial(
                                        namaSekarang || admin?.nama || 'A',
                                    )}
                                </AvatarFallback>
                            </Avatar>
                            <div className="text-sm text-muted-foreground">
                                <p>Avatar diambil dari inisial nama.</p>
                                {admin?.terakhirMasuk && (
                                    <p className="angka-tabular text-xs">
                                        Terakhir masuk{' '}
                                        {formatWaktu(admin.terakhirMasuk)}
                                    </p>
                                )}
                            </div>
                        </div>

                        <FormField
                            control={form.control}
                            name="nama"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>Nama</FormLabel>
                                    <FormControl>
                                        <Input {...field} />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />

                        <FormField
                            control={form.control}
                            name="email"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>Email</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="email"
                                            autoComplete="email"
                                            {...field}
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />

                        <div>
                            <Button
                                type="submit"
                                disabled={
                                    form.formState.isSubmitting ||
                                    !form.formState.isDirty
                                }
                            >
                                {form.formState.isSubmitting && (
                                    <Loader2Icon className="size-4 animate-spin" />
                                )}
                                Simpan profil
                            </Button>
                        </div>
                    </form>
                </Form>
            </CardContent>
        </Card>
    );
}
