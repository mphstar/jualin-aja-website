import { Link, router } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    CheckCircle2Icon,
    ChefHatIcon,
    XCircleIcon,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import { BadgeStatusPembayaran } from '@/components/shared/BadgeStatus';
import { DialogKonfirmasi } from '@/components/shared/DialogKonfirmasi';
import { ErrorState } from '@/components/shared/StateTabel';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { api, KesalahanApi } from '@/lib/api';
import type { DetailInvoice } from '@/lib/api';
import { formatRupiah, formatTanggalPanjang } from '@/lib/format';
import {
    LABEL_DURASI,
    LABEL_JENIS_USAHA,
    LABEL_METODE_PEMBAYARAN,
} from '@/lib/konstanta';

export function HalamanDetailInvoice({ id }: { id: string }) {
    const [data, setData] = useState<DetailInvoice | null>(null);
    const [memuat, setMemuat] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [bukaLunas, setBukaLunas] = useState(false);
    const [bukaGagal, setBukaGagal] = useState(false);

    const muat = useCallback(async () => {
        setMemuat(true);
        setError(null);

        try {
            setData(await api.pembayaran.ambilDetailInvoice(id));
        } catch (e) {
            setError(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Tidak dapat memuat invoice.',
            );
        } finally {
            setMemuat(false);
        }
    }, [id]);

    useEffect(() => {
        void muat();
    }, [muat]);

    if (memuat) {
        return (
            <div className="space-y-4">
                <Skeleton className="h-8 w-48" />
                <Skeleton className="h-[520px] w-full max-w-3xl" />
            </div>
        );
    }

    if (error || !data) {
        return (
            <div className="rounded-lg border">
                <ErrorState
                    pesan={error ?? 'Invoice tidak ditemukan.'}
                    onCobaLagi={() => void muat()}
                />
            </div>
        );
    }

    const { pembayaran, user } = data;
    const belumLunas = pembayaran.status !== 'LUNAS';

    async function jalankanLunas() {
        try {
            await api.pembayaran.tandaiLunas(id);
            toast.success('Invoice ditandai lunas', {
                description: `Langganan ${user.namaToko} otomatis diperpanjang ${LABEL_DURASI[pembayaran.durasi]}.`,
            });
            await muat();
        } catch (e) {
            toast.error(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Gagal menandai invoice.',
            );
        }
    }

    async function jalankanGagal() {
        try {
            await api.pembayaran.tandaiGagal(id);
            toast.success('Invoice ditandai gagal');
            await muat();
        } catch (e) {
            toast.error(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Gagal menandai invoice.',
            );
        }
    }

    return (
        <>
            <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                <Button
                    variant="ghost"
                    size="sm"
                    className="-ml-2 text-muted-foreground"
                    onClick={() => router.visit('/pembayaran')}
                >
                    <ArrowLeftIcon className="size-4" />
                    Kembali ke riwayat pembayaran
                </Button>

                {belumLunas && (
                    <div className="flex items-center gap-2">
                        {pembayaran.status !== 'GAGAL' && (
                            <Button
                                variant="outline"
                                onClick={() => setBukaGagal(true)}
                            >
                                <XCircleIcon className="size-4" />
                                Tandai gagal
                            </Button>
                        )}
                        <Button onClick={() => setBukaLunas(true)}>
                            <CheckCircle2Icon className="size-4" />
                            Tandai lunas
                        </Button>
                    </div>
                )}
            </div>

            <Card className="max-w-3xl">
                <CardContent className="p-6 sm:p-8">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <ChefHatIcon className="size-5" />
                            </div>
                            <div>
                                <p className="font-semibold">Admin POS</p>
                                <p className="text-xs text-muted-foreground">
                                    Langganan aplikasi kasir
                                </p>
                            </div>
                        </div>

                        <div className="text-right">
                            <p className="text-xs text-muted-foreground">
                                Nomor invoice
                            </p>
                            <p className="angka-tabular text-lg font-semibold">
                                {pembayaran.nomorInvoice}
                            </p>
                            <div className="mt-1 flex justify-end">
                                <BadgeStatusPembayaran
                                    status={pembayaran.status}
                                />
                            </div>
                        </div>
                    </div>

                    <Separator className="my-6" />

                    <div className="grid gap-6 sm:grid-cols-2">
                        <div>
                            <p className="mb-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Ditagihkan kepada
                            </p>
                            <Link
                                href={`/pengguna/${user.id}`}
                                className="font-medium hover:underline"
                            >
                                {user.namaToko}
                            </Link>
                            <p className="text-sm">{user.nama}</p>
                            <p className="text-sm text-muted-foreground">
                                {user.email}
                            </p>
                            <p className="angka-tabular text-sm text-muted-foreground">
                                {user.telepon}
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {LABEL_JENIS_USAHA[user.jenisUsaha]} ·{' '}
                                {user.kota}
                            </p>
                        </div>

                        <div className="sm:text-right">
                            <p className="mb-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Rincian
                            </p>
                            <Baris label="Tanggal">
                                {formatTanggalPanjang(pembayaran.tanggal)}
                            </Baris>
                            <Baris label="Metode">
                                {LABEL_METODE_PEMBAYARAN[pembayaran.metode]}
                            </Baris>
                            <Baris label="Paket">
                                {LABEL_DURASI[pembayaran.durasi]}
                            </Baris>
                        </div>
                    </div>

                    <Separator className="my-6" />

                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="font-medium">
                                Langganan {LABEL_DURASI[pembayaran.durasi]}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Akses penuh aplikasi POS dan seluruh ebook resep
                                terbit.
                            </p>
                        </div>
                        <p className="angka-tabular whitespace-nowrap">
                            {formatRupiah(pembayaran.nominal)}
                        </p>
                    </div>

                    <Separator className="my-6" />

                    <div className="flex items-center justify-between gap-4">
                        <p className="font-medium">Total</p>
                        <p className="angka-tabular text-2xl font-semibold">
                            {formatRupiah(pembayaran.nominal)}
                        </p>
                    </div>

                    {pembayaran.catatan && (
                        <>
                            <Separator className="my-6" />
                            <p className="text-sm text-muted-foreground">
                                <span className="font-medium text-foreground">
                                    Catatan:{' '}
                                </span>
                                {pembayaran.catatan}
                            </p>
                        </>
                    )}

                    {pembayaran.status === 'MENUNGGU' && (
                        <p className="mt-6 rounded-md border border-peringatan/25 bg-peringatan-lembut p-3 text-sm text-peringatan">
                            Invoice ini belum dibayar. Menandainya lunas akan
                            langsung memperpanjang masa langganan{' '}
                            {user.namaToko}.
                        </p>
                    )}
                </CardContent>
            </Card>

            <DialogKonfirmasi
                buka={bukaLunas}
                onTutup={() => setBukaLunas(false)}
                judul="Tandai invoice lunas"
                keterangan={
                    <>
                        Langganan{' '}
                        <span className="font-medium text-foreground">
                            {user.namaToko}
                        </span>{' '}
                        akan otomatis diperpanjang{' '}
                        {LABEL_DURASI[pembayaran.durasi].toLowerCase()}.
                    </>
                }
                labelKonfirmasi="Tandai lunas"
                onKonfirmasi={jalankanLunas}
            />

            <DialogKonfirmasi
                buka={bukaGagal}
                onTutup={() => setBukaGagal(false)}
                judul="Tandai invoice gagal"
                keterangan="Masa langganan tidak akan bertambah."
                labelKonfirmasi="Tandai gagal"
                destruktif
                onKonfirmasi={jalankanGagal}
            />
        </>
    );
}

function Baris({ label, children }: { label: string; children: ReactNode }) {
    return (
        <p className="text-sm">
            <span className="text-muted-foreground">{label}: </span>
            <span className="font-medium">{children}</span>
        </p>
    );
}
