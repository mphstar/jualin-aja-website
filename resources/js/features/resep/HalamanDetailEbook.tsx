import { Link, router } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    CalendarIcon,
    DownloadIcon,
    EyeOffIcon,
    FileTextIcon,
    PencilIcon,
    SendIcon,
    Trash2Icon,
    UsersIcon,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { toast } from 'sonner';
import { BadgeStatusEbook } from '@/components/shared/BadgeStatus';
import { DialogKonfirmasi } from '@/components/shared/DialogKonfirmasi';
import { StatCard } from '@/components/shared/StatCard';
import { EmptyState, ErrorState } from '@/components/shared/StateTabel';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { SampulEbook } from '@/features/resep/SampulEbook';
import { api, KesalahanApi } from '@/lib/api';
import type { DetailEbook } from '@/lib/api';
import {
    formatAngka,
    formatTanggal,
    formatUkuranFile,
    formatWaktu,
} from '@/lib/format';
import { LABEL_KATEGORI_EBOOK } from '@/lib/konstanta';

const KONFIG_GRAFIK = {
    nilai: { label: 'Unduhan', color: 'var(--chart-1)' },
} satisfies ChartConfig;

export function HalamanDetailEbook({ id }: { id: string }) {
    const [detail, setDetail] = useState<DetailEbook | null>(null);
    const [memuat, setMemuat] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [bukaHapus, setBukaHapus] = useState(false);
    const [mengubahStatus, setMengubahStatus] = useState(false);

    const muat = useCallback(async () => {
        setMemuat(true);
        setError(null);

        try {
            setDetail(await api.ebook.ambilDetailEbook(id));
        } catch (e) {
            setError(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Tidak dapat memuat ebook.',
            );
        } finally {
            setMemuat(false);
        }
    }, [id]);

    useEffect(() => {
        void muat();
    }, [muat]);

    if (memuat) {
        return <KerangkaDetail />;
    }

    if (error || !detail) {
        return (
            <div className="rounded-lg border">
                <ErrorState
                    pesan={error ?? 'Ebook tidak ditemukan.'}
                    onCobaLagi={() => void muat()}
                />
            </div>
        );
    }

    const { ebook, unduhan, deret30Hari } = detail;
    const terbit = ebook.status === 'TERBIT';
    const unduhan30Hari = deret30Hari.reduce((t, d) => t + d.nilai, 0);
    // Beri satu satuan ruang di atas batang tertinggi supaya tidak mentok.
    const puncakDeret = Math.max(1, ...deret30Hari.map((d) => d.nilai)) + 1;
    const tokoUnik = new Set(unduhan.map((u) => u.userId)).size;

    async function gantiStatus() {
        setMengubahStatus(true);

        try {
            await api.ebook.ubahStatusEbook(id, terbit ? 'DRAF' : 'TERBIT');
            toast.success(terbit ? 'Ebook jadi draf' : 'Ebook diterbitkan');
            await muat();
        } catch (e) {
            toast.error(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Gagal mengubah status.',
            );
        } finally {
            setMengubahStatus(false);
        }
    }

    async function jalankanHapus() {
        try {
            await api.ebook.hapusEbook(id);
            toast.success('Ebook dihapus', {
                description: `"${ebook.judul}" tidak ada lagi di katalog.`,
            });
            router.visit('/resep');
        } catch (e) {
            toast.error(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Gagal menghapus ebook.',
            );
        }
    }

    return (
        <>
            <Button
                variant="ghost"
                size="sm"
                className="mb-3 -ml-2 text-muted-foreground"
                onClick={() => router.visit('/resep')}
            >
                <ArrowLeftIcon className="size-4" />
                Kembali ke katalog
            </Button>

            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div className="flex min-w-0 gap-4">
                    <div className="hidden h-24 w-36 shrink-0 overflow-hidden rounded-lg border sm:block">
                        <SampulEbook
                            kategori={ebook.kategori}
                            coverUrl={ebook.coverUrl}
                            judul={ebook.judul}
                        />
                    </div>
                    <div className="min-w-0">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {ebook.judul}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {LABEL_KATEGORI_EBOOK[ebook.kategori]}
                        </p>
                        <div className="mt-2">
                            <BadgeStatusEbook status={ebook.status} />
                        </div>
                    </div>
                </div>

                <div className="flex shrink-0 flex-wrap items-center gap-2">
                    <Button variant="outline" asChild>
                        <Link href={`/resep/${ebook.id}/ubah`}>
                            <PencilIcon className="size-4" />
                            Ubah
                        </Link>
                    </Button>
                    <Button onClick={gantiStatus} disabled={mengubahStatus}>
                        {terbit ? (
                            <>
                                <EyeOffIcon className="size-4" />
                                Jadikan draf
                            </>
                        ) : (
                            <>
                                <SendIcon className="size-4" />
                                Terbitkan
                            </>
                        )}
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        onClick={() => setBukaHapus(true)}
                        aria-label="Hapus ebook"
                    >
                        <Trash2Icon className="size-4 text-destructive" />
                    </Button>
                </div>
            </div>

            <div className="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <StatCard
                    judul="Total unduhan"
                    nilai={formatAngka(ebook.jumlahUnduhan)}
                    ikon={DownloadIcon}
                    keterangan="sejak diterbitkan"
                />
                <StatCard
                    judul="Unduhan 30 hari terakhir"
                    nilai={formatAngka(unduhan30Hari)}
                    ikon={CalendarIcon}
                />
                <StatCard
                    judul="Toko yang mengunduh"
                    nilai={formatAngka(tokoUnik)}
                    ikon={UsersIcon}
                    keterangan="toko berbeda"
                />
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle className="text-base">
                            Unduhan 30 hari terakhir
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {unduhan30Hari === 0 ? (
                            <EmptyState
                                ikon={<DownloadIcon className="size-8" />}
                                judul="Belum ada unduhan bulan ini"
                                keterangan={
                                    terbit
                                        ? 'Ebook ini belum diunduh dalam 30 hari terakhir.'
                                        : 'Ebook berstatus draf tidak terlihat oleh pelanggan.'
                                }
                            />
                        ) : (
                            <ChartContainer
                                config={KONFIG_GRAFIK}
                                className="h-56 w-full"
                            >
                                <BarChart data={deret30Hari}>
                                    <CartesianGrid vertical={false} />
                                    <XAxis
                                        dataKey="label"
                                        tickLine={false}
                                        axisLine={false}
                                        tickMargin={8}
                                        interval={4}
                                    />
                                    {/*
                    Domain WAJIB eksplisit. Dengan domain otomatis, recharts
                    menggambar sumbu memakai batas yang sudah "dibulatkan
                    cantik" tapi menskala batangnya memakai batas mentah —
                    batang jadi tampak nyaris rata nol. Fungsi di bawah juga
                    memberi sedikit ruang di atas batang tertinggi.
                  */}
                                    <YAxis
                                        tickLine={false}
                                        axisLine={false}
                                        width={28}
                                        allowDecimals={false}
                                        domain={[0, puncakDeret]}
                                    />
                                    <ChartTooltip
                                        content={<ChartTooltipContent />}
                                    />
                                    {/*
                    Animasi dimatikan: recharts menganimasikan batang dari
                    tinggi 0 lewat requestAnimationFrame, dan rAF di-throttle
                    saat tab tidak aktif — grafik bisa berhenti selamanya di
                    posisi awal (tampak rata nol padahal datanya ada).
                  */}
                                    <Bar
                                        dataKey="nilai"
                                        fill="var(--color-nilai)"
                                        radius={3}
                                        isAnimationActive={false}
                                    />
                                </BarChart>
                            </ChartContainer>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Metadata</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3 text-sm">
                        <Baris label="Berkas">
                            <span className="inline-flex items-center gap-1.5">
                                <FileTextIcon className="size-4 text-muted-foreground" />
                                <span className="max-w-[160px] truncate">
                                    {ebook.namaFile ?? '—'}
                                </span>
                            </span>
                        </Baris>
                        <Separator />
                        <Baris label="Ukuran">
                            <span className="angka-tabular">
                                {ebook.ukuranFileBytes
                                    ? formatUkuranFile(ebook.ukuranFileBytes)
                                    : '—'}
                            </span>
                        </Baris>
                        <Separator />
                        <Baris label="Halaman">
                            <span className="angka-tabular">
                                {ebook.jumlahHalaman ?? '—'}
                            </span>
                        </Baris>
                        <Separator />
                        <Baris label="Dibuat">
                            <span className="angka-tabular">
                                {formatTanggal(ebook.tanggalDibuat)}
                            </span>
                        </Baris>
                        <Separator />
                        <Baris label="Terbit">
                            <span className="angka-tabular">
                                {ebook.tanggalTerbit
                                    ? formatTanggal(ebook.tanggalTerbit)
                                    : 'Belum terbit'}
                            </span>
                        </Baris>
                    </CardContent>
                </Card>
            </div>

            <Card className="mt-4">
                <CardHeader>
                    <CardTitle className="text-base">Deskripsi</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-sm leading-relaxed text-muted-foreground">
                        {ebook.deskripsi}
                    </p>
                </CardContent>
            </Card>

            <Card className="mt-4 gap-0 py-0">
                <CardHeader className="border-b py-4">
                    <CardTitle className="text-base">
                        Riwayat unduhan ({formatAngka(unduhan.length)})
                    </CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    {unduhan.length === 0 ? (
                        <EmptyState
                            ikon={<DownloadIcon className="size-8" />}
                            judul="Belum pernah diunduh"
                            keterangan="Unduhan dari aplikasi POS akan tercatat di sini."
                        />
                    ) : (
                        <div className="max-h-[420px] overflow-auto">
                            <Table>
                                <TableHeader className="sticky top-0 z-10 bg-background">
                                    <TableRow className="hover:bg-transparent">
                                        <TableHead>Toko</TableHead>
                                        <TableHead className="text-right">
                                            Waktu unduh
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {unduhan.map((u) => (
                                        <TableRow key={u.id}>
                                            <TableCell>
                                                <Link
                                                    href={`/pengguna/${u.userId}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {u.namaToko}
                                                </Link>
                                                <p className="text-xs text-muted-foreground">
                                                    {u.namaUser}
                                                </p>
                                            </TableCell>
                                            <TableCell className="angka-tabular text-right text-sm whitespace-nowrap text-muted-foreground">
                                                {formatWaktu(u.tanggal)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </CardContent>
            </Card>

            <DialogKonfirmasi
                buka={bukaHapus}
                onTutup={() => setBukaHapus(false)}
                judul="Hapus ebook"
                keterangan={
                    <>
                        Ebook{' '}
                        <span className="font-medium text-foreground">
                            {ebook.judul}
                        </span>{' '}
                        akan dihapus permanen beserta catatan unduhannya.
                    </>
                }
                ketikUntukKonfirmasi={ebook.judul}
                labelKonfirmasi="Hapus permanen"
                destruktif
                onKonfirmasi={jalankanHapus}
            />
        </>
    );
}

function Baris({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-4">
            <span className="text-muted-foreground">{label}</span>
            <span className="font-medium">{children}</span>
        </div>
    );
}

function KerangkaDetail() {
    return (
        <div className="space-y-4">
            <Skeleton className="h-8 w-44" />
            <div className="flex gap-4">
                <Skeleton className="h-24 w-36" />
                <div className="space-y-2">
                    <Skeleton className="h-7 w-72" />
                    <Skeleton className="h-4 w-32" />
                    <Skeleton className="h-5 w-20" />
                </div>
            </div>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Skeleton className="h-28" />
                <Skeleton className="h-28" />
                <Skeleton className="h-28" />
            </div>
            <Skeleton className="h-72" />
        </div>
    );
}
