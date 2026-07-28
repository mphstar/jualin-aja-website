import { Link, router } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    BanIcon,
    BookOpenIcon,
    CreditCardIcon,
    MailIcon,
    MapPinIcon,
    PackageIcon,
    PhoneIcon,
    ReceiptTextIcon,
    RotateCcwIcon,
    StoreIcon,
    TicketPlusIcon,
    TriangleAlertIcon,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import {
    BadgeSisaHari,
    BadgeStatusLangganan,
    BadgeStatusPembayaran,
} from '@/components/shared/BadgeStatus';
import { DialogKonfirmasi } from '@/components/shared/DialogKonfirmasi';
import { EmptyState, ErrorState } from '@/components/shared/StateTabel';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { DialogPerpanjang } from '@/features/langganan/DialogPerpanjang';
import type { TargetPerpanjang } from '@/features/langganan/DialogPerpanjang';
import { DialogTangguhkan } from '@/features/pengguna/DialogTangguhkan';
import { api, KesalahanApi } from '@/lib/api';
import type { DetailPengguna, RingkasanPos } from '@/lib/api';
import {
    formatRupiah,
    formatTanggal,
    formatWaktu,
    inisial,
} from '@/lib/format';
import {
    LABEL_DURASI,
    LABEL_JENIS_USAHA,
    LABEL_METODE_PEMBAYARAN,
    LABEL_SUMBER_LANGGANAN,
} from '@/lib/konstanta';

export function HalamanDetailPengguna({ id }: { id: string }) {
    const [detail, setDetail] = useState<DetailPengguna | null>(null);
    const [memuat, setMemuat] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const [bukaPerpanjang, setBukaPerpanjang] =
        useState<TargetPerpanjang | null>(null);
    const [bukaTangguhkan, setBukaTangguhkan] = useState(false);
    const [bukaPulihkan, setBukaPulihkan] = useState(false);

    const muat = useCallback(async () => {
        setMemuat(true);
        setError(null);

        try {
            setDetail(await api.pengguna.ambilDetailPengguna(id));
        } catch (e) {
            setError(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Tidak dapat memuat detail pengguna.',
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
                    pesan={error ?? 'Pengguna tidak ditemukan.'}
                    onCobaLagi={() => void muat()}
                />
            </div>
        );
    }

    const {
        user,
        riwayatLangganan,
        riwayatPembayaran,
        riwayatUnduhan,
        ringkasanPos,
    } = detail;

    async function pulihkan() {
        try {
            await api.pengguna.pulihkanPengguna(id);
            toast.success('Akun dipulihkan');
            await muat();
        } catch (e) {
            toast.error(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Gagal memulihkan akun.',
            );
        }
    }

    return (
        <>
            <Button
                variant="ghost"
                size="sm"
                className="mb-3 -ml-2 text-muted-foreground"
                onClick={() => router.visit('/pengguna')}
            >
                <ArrowLeftIcon className="size-4" />
                Kembali ke daftar pengguna
            </Button>

            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div className="flex min-w-0 items-center gap-4">
                    <Avatar className="size-14">
                        <AvatarFallback className="text-lg">
                            {inisial(user.nama)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="min-w-0">
                        <h1 className="truncate text-2xl font-semibold tracking-tight">
                            {user.namaToko}
                        </h1>
                        <p className="truncate text-sm text-muted-foreground">
                            {user.nama} · {LABEL_JENIS_USAHA[user.jenisUsaha]} ·{' '}
                            {user.kota}
                        </p>
                        <div className="mt-2 flex flex-wrap items-center gap-2">
                            <BadgeStatusLangganan status={user.status} />
                            {user.langgananAktif && (
                                <BadgeSisaHari
                                    hari={user.sisaHari}
                                    nonaktif={user.ditangguhkan}
                                />
                            )}
                        </div>
                    </div>
                </div>

                <div className="flex shrink-0 flex-wrap items-center gap-2">
                    <Button
                        onClick={() =>
                            setBukaPerpanjang({
                                userId: user.id,
                                namaToko: user.namaToko,
                                langgananAktif: user.langgananAktif,
                            })
                        }
                    >
                        <TicketPlusIcon className="size-4" />
                        Perpanjang
                    </Button>
                    {user.ditangguhkan ? (
                        <Button
                            variant="outline"
                            onClick={() => setBukaPulihkan(true)}
                        >
                            <RotateCcwIcon className="size-4" />
                            Pulihkan
                        </Button>
                    ) : (
                        <Button
                            variant="outline"
                            onClick={() => setBukaTangguhkan(true)}
                        >
                            <BanIcon className="size-4" />
                            Tangguhkan
                        </Button>
                    )}
                </div>
            </div>

            {user.ditangguhkan && (
                <div className="mb-6 flex items-start gap-3 rounded-lg border border-destructive/25 bg-bahaya-lembut p-4 text-sm text-destructive">
                    <TriangleAlertIcon className="mt-0.5 size-4 shrink-0" />
                    <div>
                        <p className="font-medium">Akun ditangguhkan</p>
                        <p className="mt-0.5">
                            {user.alasanPenangguhan ??
                                'Tidak ada alasan tercatat.'}
                        </p>
                    </div>
                </div>
            )}

            <Tabs defaultValue="profil">
                <TabsList>
                    <TabsTrigger value="profil">Profil</TabsTrigger>
                    <TabsTrigger value="langganan">
                        Langganan ({riwayatLangganan.length})
                    </TabsTrigger>
                    <TabsTrigger value="pembayaran">
                        Pembayaran ({riwayatPembayaran.length})
                    </TabsTrigger>
                    <TabsTrigger value="unduhan">
                        Unduhan ({riwayatUnduhan.length})
                    </TabsTrigger>
                    <TabsTrigger value="kasir">Kasir</TabsTrigger>
                </TabsList>

                {/* ---------------- Profil ---------------- */}
                <TabsContent value="profil" className="mt-4">
                    <div className="grid gap-4 lg:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Kontak pemilik
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-3">
                                <BarisData
                                    ikon={<MailIcon className="size-4" />}
                                    label="Email"
                                >
                                    {user.email}
                                </BarisData>
                                <Separator />
                                <BarisData
                                    ikon={<PhoneIcon className="size-4" />}
                                    label="Telepon"
                                >
                                    <span className="angka-tabular">
                                        {user.telepon}
                                    </span>
                                </BarisData>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Data toko
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-3">
                                <BarisData
                                    ikon={<StoreIcon className="size-4" />}
                                    label="Jenis usaha"
                                >
                                    {LABEL_JENIS_USAHA[user.jenisUsaha]}
                                </BarisData>
                                <Separator />
                                <BarisData
                                    ikon={<MapPinIcon className="size-4" />}
                                    label="Kota"
                                >
                                    {user.kota}
                                </BarisData>
                                <Separator />
                                <BarisData
                                    ikon={<CreditCardIcon className="size-4" />}
                                    label="Terdaftar sejak"
                                >
                                    {formatTanggal(user.tanggalDaftar)}
                                </BarisData>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                {/* ---------------- Langganan ---------------- */}
                <TabsContent value="langganan" className="mt-4">
                    <div className="overflow-hidden rounded-lg border">
                        {riwayatLangganan.length === 0 ? (
                            <EmptyState
                                judul="Belum pernah berlangganan"
                                keterangan="Perpanjangan pertama akan muncul di sini."
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="hover:bg-transparent">
                                            <TableHead>Paket</TableHead>
                                            <TableHead>Sumber</TableHead>
                                            <TableHead>Mulai</TableHead>
                                            <TableHead>Berakhir</TableHead>
                                            <TableHead>Catatan</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {riwayatLangganan.map((l) => (
                                            <TableRow key={l.id}>
                                                <TableCell className="whitespace-nowrap">
                                                    <span className="font-medium">
                                                        {LABEL_DURASI[l.durasi]}
                                                    </span>
                                                    {l.id ===
                                                        user.langgananAktif
                                                            ?.id && (
                                                        <span className="ml-2 rounded border border-sukses/25 bg-sukses-lembut px-1.5 py-0.5 text-xs font-medium text-sukses">
                                                            Berlaku
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-sm whitespace-nowrap text-muted-foreground">
                                                    {
                                                        LABEL_SUMBER_LANGGANAN[
                                                            l.sumber
                                                        ]
                                                    }
                                                </TableCell>
                                                <TableCell className="angka-tabular text-sm whitespace-nowrap">
                                                    {formatTanggal(
                                                        l.tanggalMulai,
                                                    )}
                                                </TableCell>
                                                <TableCell className="angka-tabular text-sm whitespace-nowrap">
                                                    {formatTanggal(
                                                        l.tanggalBerakhir,
                                                    )}
                                                </TableCell>
                                                <TableCell className="max-w-[280px] truncate text-sm text-muted-foreground">
                                                    {l.catatan ?? '—'}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </div>
                </TabsContent>

                {/* ---------------- Pembayaran ---------------- */}
                <TabsContent value="pembayaran" className="mt-4">
                    <div className="overflow-hidden rounded-lg border">
                        {riwayatPembayaran.length === 0 ? (
                            <EmptyState
                                ikon={<CreditCardIcon className="size-8" />}
                                judul="Belum ada pembayaran"
                                keterangan="Toko ini belum pernah membuat tagihan."
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="hover:bg-transparent">
                                            <TableHead>Invoice</TableHead>
                                            <TableHead>Tanggal</TableHead>
                                            <TableHead>Paket</TableHead>
                                            <TableHead>Metode</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">
                                                Nominal
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {riwayatPembayaran.map((p) => (
                                            <TableRow key={p.id}>
                                                <TableCell className="whitespace-nowrap">
                                                    <Link
                                                        href={`/pembayaran/${p.id}`}
                                                        className="angka-tabular font-medium hover:underline"
                                                    >
                                                        {p.nomorInvoice}
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="angka-tabular text-sm whitespace-nowrap text-muted-foreground">
                                                    {formatTanggal(p.tanggal)}
                                                </TableCell>
                                                <TableCell className="text-sm whitespace-nowrap">
                                                    {LABEL_DURASI[p.durasi]}
                                                </TableCell>
                                                <TableCell className="text-sm whitespace-nowrap text-muted-foreground">
                                                    {
                                                        LABEL_METODE_PEMBAYARAN[
                                                            p.metode
                                                        ]
                                                    }
                                                </TableCell>
                                                <TableCell>
                                                    <BadgeStatusPembayaran
                                                        status={p.status}
                                                    />
                                                </TableCell>
                                                <TableCell className="angka-tabular text-right font-medium whitespace-nowrap">
                                                    {formatRupiah(p.nominal)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </div>
                </TabsContent>

                {/* ---------------- Unduhan ---------------- */}
                <TabsContent value="unduhan" className="mt-4">
                    <div className="overflow-hidden rounded-lg border">
                        {riwayatUnduhan.length === 0 ? (
                            <EmptyState
                                ikon={<BookOpenIcon className="size-8" />}
                                judul="Belum ada ebook diunduh"
                                keterangan="Ebook yang diunduh lewat aplikasi POS akan tercatat di sini."
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="hover:bg-transparent">
                                            <TableHead>Ebook</TableHead>
                                            <TableHead className="text-right">
                                                Waktu unduh
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {riwayatUnduhan.map((u) => (
                                            <TableRow key={u.id}>
                                                <TableCell>
                                                    <Link
                                                        href={`/resep/${u.ebookId}`}
                                                        className="font-medium hover:underline"
                                                    >
                                                        {u.judulEbook}
                                                    </Link>
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
                    </div>
                </TabsContent>
                {/* ---------------- Kasir (aplikasi POS) ---------------- */}
                <TabsContent value="kasir" className="mt-4">
                    <PanelKasir ringkasan={ringkasanPos} />
                </TabsContent>
            </Tabs>

            <DialogPerpanjang
                target={bukaPerpanjang}
                onTutup={() => setBukaPerpanjang(null)}
                onBerhasil={() => void muat()}
            />

            <DialogTangguhkan
                target={
                    bukaTangguhkan
                        ? { id: user.id, namaToko: user.namaToko }
                        : null
                }
                onTutup={() => setBukaTangguhkan(false)}
                onKirim={async (idUser, alasan) => {
                    await api.pengguna.tangguhkanPengguna(idUser, alasan);
                    await muat();
                }}
            />

            <DialogKonfirmasi
                buka={bukaPulihkan}
                onTutup={() => setBukaPulihkan(false)}
                judul="Pulihkan akun"
                keterangan={
                    <>
                        <span className="font-medium text-foreground">
                            {user.namaToko}
                        </span>{' '}
                        akan bisa mengakses aplikasi POS lagi sesuai masa
                        langganannya.
                    </>
                }
                labelKonfirmasi="Pulihkan"
                onKonfirmasi={pulihkan}
            />
        </>
    );
}

function BarisData({
    ikon,
    label,
    children,
}: {
    ikon: ReactNode;
    label: string;
    children: ReactNode;
}) {
    return (
        <div className="flex items-center justify-between gap-4">
            <span className="flex items-center gap-2 text-sm text-muted-foreground">
                {ikon}
                {label}
            </span>
            <span className="min-w-0 truncate text-sm font-medium">
                {children}
            </span>
        </div>
    );
}

function KerangkaDetail() {
    return (
        <>
            <Skeleton className="mb-4 h-8 w-52" />
            <div className="mb-6 flex items-center gap-4">
                <Skeleton className="size-14 rounded-full" />
                <div className="space-y-2">
                    <Skeleton className="h-7 w-56" />
                    <Skeleton className="h-4 w-72" />
                    <Skeleton className="h-5 w-40" />
                </div>
            </div>
            <Skeleton className="h-9 w-full max-w-lg" />
            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                <Skeleton className="h-48" />
                <Skeleton className="h-48" />
            </div>
        </>
    );
}

/**
 * Ringkasan pemakaian kasir.
 *
 * Pertanyaan yang dijawabnya bukan "sudah bayar belum" — itu sudah ada di tab
 * Langganan — melainkan **"benar-benar dipakai tidak?"**. Toko berlangganan
 * setahun yang nol transaksi selama sebulan adalah toko yang tidak akan
 * memperpanjang, dan itu satu-satunya sinyal yang muncul sebelum ia pergi.
 */
function PanelKasir({ ringkasan }: { ringkasan: RingkasanPos }) {
    const belumDipakai = ringkasan.produk === 0;

    if (belumDipakai) {
        return (
            <div className="overflow-hidden rounded-lg border">
                <EmptyState
                    ikon={<PackageIcon className="size-8" />}
                    judul="Kasir belum disiapkan"
                    keterangan="Toko ini belum menambahkan satu pun produk lewat aplikasi POS."
                />
            </div>
        );
    }

    return (
        <div className="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Master data</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-3">
                    <BarisData
                        ikon={<PackageIcon className="size-4" />}
                        label="Produk"
                    >
                        <span className="angka-tabular">
                            {ringkasan.produk} produk · {ringkasan.kategori}{' '}
                            kategori
                        </span>
                    </BarisData>
                    <Separator />
                    <BarisData
                        ikon={<TriangleAlertIcon className="size-4" />}
                        label="Stok habis"
                    >
                        <span
                            className={
                                ringkasan.produkHabis > 0
                                    ? 'angka-tabular text-peringatan'
                                    : 'angka-tabular'
                            }
                        >
                            {ringkasan.produkHabis} produk
                        </span>
                    </BarisData>
                    <Separator />
                    <BarisData
                        ikon={<ReceiptTextIcon className="size-4" />}
                        label="Transaksi terakhir"
                    >
                        {ringkasan.transaksiTerakhir
                            ? formatWaktu(ringkasan.transaksiTerakhir)
                            : 'Belum pernah'}
                    </BarisData>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Aktivitas 30 hari
                    </CardTitle>
                </CardHeader>
                <CardContent className="grid gap-3">
                    <BarisData
                        ikon={<ReceiptTextIcon className="size-4" />}
                        label="Transaksi"
                    >
                        <span className="angka-tabular">
                            {ringkasan.transaksi30Hari} struk
                        </span>
                    </BarisData>
                    <Separator />
                    <BarisData
                        ikon={<CreditCardIcon className="size-4" />}
                        label="Omzet"
                    >
                        <span className="angka-tabular">
                            {formatRupiah(ringkasan.omzet30Hari)}
                        </span>
                    </BarisData>
                    <Separator />
                    <BarisData
                        ikon={<TicketPlusIcon className="size-4" />}
                        label="Bayar nanti"
                    >
                        <span className="angka-tabular">
                            {ringkasan.piutangJumlah} struk ·{' '}
                            {formatRupiah(ringkasan.piutangTotal)}
                        </span>
                    </BarisData>

                    {!ringkasan.aktif && (
                        <p className="mt-1 rounded-md bg-peringatan-lembut px-3 py-2 text-xs text-peringatan">
                            Tidak ada transaksi sama sekali dalam 30 hari
                            terakhir, padahal produknya sudah disiapkan.
                        </p>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
