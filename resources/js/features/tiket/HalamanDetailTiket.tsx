import { Link, router } from '@inertiajs/react';
import {
    AlertCircleIcon,
    ArrowLeftIcon,
    CalendarIcon,
    CheckCircle2Icon,
    ClockIcon,
    CopyIcon,
    MailIcon,
    MessageSquareIcon,
    SendIcon,
    ShieldCheckIcon,
    StoreIcon,
    UserIcon,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { Textarea } from '@/components/ui/textarea';
import { api, KesalahanApi } from '@/lib/api';
import type { TiketData } from '@/lib/api/tiket';
import { inisial } from '@/lib/format';

export function HalamanDetailTiket({ id }: { id: string }) {
    const [tiket, setTiket] = useState<TiketData | null>(null);
    const [memuat, setMemuat] = useState(true);
    const [balasan, setBalasan] = useState('');
    const [status, setStatus] = useState<string>('SELESAI');
    const [mengirim, setMengirim] = useState(false);

    const muatDetail = async () => {
        setMemuat(true);
        try {
            const data = await api.tiket.ambilDetailTiket(id);
            setTiket(data);
            if (data.balasanAdmin) {
                setBalasan(data.balasanAdmin);
            }
            setStatus(data.status);
        } catch (e) {
            const pesan = e instanceof KesalahanApi ? e.message : 'Gagal memuat detail tiket.';
            toast.error(pesan);
        } finally {
            setMemuat(false);
        }
    };

    useEffect(() => {
        muatDetail();
    }, [id]);

    const handleKirimBalasan = async () => {
        if (!balasan.trim()) {
            toast.error('Pesan balasan admin tidak boleh kosong.');
            return;
        }

        setMengirim(true);
        try {
            const res = await api.tiket.kirimBalasanTiket(id, balasan.trim(), status);
            setTiket(res);
            toast.success('Tanggapan resmi admin berhasil dikirim & status diperbarui.');
        } catch (e) {
            const pesan = e instanceof KesalahanApi ? e.message : 'Gagal mengirim balasan.';
            toast.error(pesan);
        } finally {
            setMengirim(false);
        }
    };

    const salinEmail = (email: string) => {
        navigator.clipboard.writeText(email);
        toast.success('Email berhasil disalin');
    };

    if (memuat) {
        return (
            <div className="space-y-6 max-w-5xl mx-auto">
                <Skeleton className="h-10 w-48" />
                <Skeleton className="h-24 w-full" />
                <div className="grid gap-6 md:grid-cols-3">
                    <div className="md:col-span-2 space-y-6">
                        <Skeleton className="h-48 w-full" />
                        <Skeleton className="h-64 w-full" />
                    </div>
                    <Skeleton className="h-64 w-full" />
                </div>
            </div>
        );
    }

    if (!tiket) {
        return (
            <div className="py-16 text-center">
                <AlertCircleIcon className="mx-auto h-12 w-12 text-muted-foreground mb-3 opacity-60" />
                <p className="text-lg font-medium text-foreground mb-1">Tiket Tidak Ditemukan</p>
                <p className="text-sm text-muted-foreground mb-4">
                    Laporan atau tiket bantuan dengan ID ini tidak tersedia di database.
                </p>
                <Button variant="outline" onClick={() => router.visit('/tiket')}>
                    <ArrowLeftIcon className="h-4 w-4 mr-2" /> Kembali ke Daftar Tiket
                </Button>
            </div>
        );
    }

    return (
        <div className="space-y-6 max-w-5xl mx-auto">
            {/* Header Top Nav */}
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <Button
                    variant="ghost"
                    size="sm"
                    className="-ml-2 w-fit text-muted-foreground hover:text-foreground"
                    onClick={() => router.visit('/tiket')}
                >
                    <ArrowLeftIcon className="h-4 w-4 mr-1.5" /> Kembali ke Daftar Tiket
                </Button>
                <div className="flex items-center gap-2">
                    <Badge variant="outline" className="font-mono text-xs px-2.5 py-1">
                        {tiket.nomorTiket}
                    </Badge>
                </div>
            </div>

            {/* Banner Header Status */}
            <Card className="overflow-hidden border-primary/20 bg-gradient-to-r from-primary/5 via-background to-background">
                <CardContent className="p-6">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div className="space-y-1">
                            <div className="flex items-center gap-2">
                                <Badge
                                    variant={
                                        tiket.jenis === 'SARAN'
                                            ? 'secondary'
                                            : tiket.jenis === 'KOMPLAIN'
                                            ? 'destructive'
                                            : 'outline'
                                    }
                                >
                                    {tiket.jenisLabel}
                                </Badge>
                                <Badge
                                    variant={
                                        tiket.status === 'SELESAI'
                                            ? 'default'
                                            : tiket.status === 'DIPROSES'
                                            ? 'secondary'
                                            : tiket.status === 'TERBUKA'
                                            ? 'destructive'
                                            : 'outline'
                                    }
                                >
                                    {tiket.statusLabel}
                                </Badge>
                            </div>
                            <h1 className="text-xl font-bold tracking-tight text-foreground pt-1">
                                {tiket.subjek}
                            </h1>
                            <p className="text-xs text-muted-foreground flex items-center gap-1.5 pt-0.5">
                                <CalendarIcon className="h-3.5 w-3.5" />
                                Dikirim pada {new Date(tiket.dibuatPada).toLocaleString('id-ID', {
                                    day: 'numeric',
                                    month: 'long',
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                })}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div className="grid gap-6 md:grid-cols-3">
                {/* Kolom Kiri: Utas Percakapan (Thread) & Form Admin */}
                <div className="md:col-span-2 space-y-6">
                    {/* Utas Percakapan / Pesan */}
                    <Card>
                        <CardHeader className="pb-3 border-b">
                            <CardTitle className="text-base font-semibold flex items-center gap-2">
                                <MessageSquareIcon className="h-4 w-4 text-primary" />
                                Riwayat Percakapan Laporan
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-6 space-y-6">
                            {/* Pesan dari Pengguna */}
                            <div className="flex items-start gap-4">
                                <Avatar className="h-10 w-10 border border-primary/20 shadow-sm">
                                    <AvatarFallback className="bg-primary/10 text-primary font-bold">
                                        {inisial(tiket.toko.nama)}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="flex-1 space-y-1.5">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <span className="font-semibold text-sm">{tiket.toko.nama}</span>
                                            <span className="text-xs text-muted-foreground ml-2">({tiket.toko.namaToko})</span>
                                        </div>
                                        <span className="text-xs text-muted-foreground">
                                            {new Date(tiket.dibuatPada).toLocaleTimeString('id-ID', {
                                                hour: '2-digit',
                                                minute: '2-digit',
                                            })}
                                        </span>
                                    </div>
                                    <div className="p-4 rounded-xl bg-muted/60 border text-sm leading-relaxed whitespace-pre-wrap">
                                        {tiket.pesan}
                                    </div>
                                </div>
                            </div>

                            {/* Balasan Admin (Jika sudah ada) */}
                            {tiket.balasanAdmin && (
                                <div className="flex items-start gap-4 pt-2">
                                    <Avatar className="h-10 w-10 border border-emerald-500/30 shadow-sm">
                                        <AvatarFallback className="bg-emerald-500/10 text-emerald-600 font-bold">
                                            <ShieldCheckIcon className="h-5 w-5" />
                                        </AvatarFallback>
                                    </Avatar>
                                    <div className="flex-1 space-y-1.5">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <span className="font-semibold text-sm text-emerald-950 dark:text-emerald-300">
                                                    {tiket.adminNama || 'Admin Support'}
                                                </span>
                                                <Badge variant="default" className="bg-emerald-600 hover:bg-emerald-600 text-[10px] py-0 px-2">
                                                    Tim Resmi
                                                </Badge>
                                            </div>
                                            <span className="text-xs text-muted-foreground">
                                                {tiket.dibalasPada
                                                    ? new Date(tiket.dibalasPada).toLocaleString('id-ID', {
                                                          day: 'numeric',
                                                          month: 'short',
                                                          hour: '2-digit',
                                                          minute: '2-digit',
                                                      })
                                                    : '-'}
                                            </span>
                                        </div>
                                        <div className="p-4 rounded-xl bg-emerald-50/80 border border-emerald-200 text-emerald-950 dark:bg-emerald-950/30 dark:border-emerald-800 dark:text-emerald-200 text-sm leading-relaxed whitespace-pre-wrap">
                                            {tiket.balasanAdmin}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Form Respon Admin */}
                    <Card className="border-primary/30 shadow-sm">
                        <CardHeader className="pb-3 border-b bg-muted/30">
                            <CardTitle className="text-base font-semibold flex items-center gap-2">
                                <SendIcon className="h-4 w-4 text-primary" />
                                Respon Resmi Admin
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-6 space-y-4">
                            <div>
                                <Label htmlFor="status" className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                    Status Tiket Terkini
                                </Label>
                                <Select value={status} onValueChange={setStatus}>
                                    <SelectTrigger className="w-full mt-1.5">
                                        <SelectValue placeholder="Pilih Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="DIPROSES">
                                            <div className="flex items-center gap-2">
                                                <ClockIcon className="h-4 w-4 text-amber-500" />
                                                <span>Diproses (Dalam Pengerjaan)</span>
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="SELESAI">
                                            <div className="flex items-center gap-2">
                                                <CheckCircle2Icon className="h-4 w-4 text-emerald-500" />
                                                <span>Selesai (Sudah Ditanggapi)</span>
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="DITUTUP">
                                            <div className="flex items-center gap-2">
                                                <AlertCircleIcon className="h-4 w-4 text-slate-500" />
                                                <span>Ditutup (Arsip Laporan)</span>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label htmlFor="balasan" className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                    Pesan Balasan Resmi ke Pengguna
                                </Label>
                                <Textarea
                                    id="balasan"
                                    rows={5}
                                    placeholder="Tuliskan respon resmi, solusi kendala, atau pesan apresiasi saran di sini..."
                                    value={balasan}
                                    onChange={(e) => setBalasan(e.target.value)}
                                    className="mt-1.5 focus-visible:ring-primary"
                                />
                            </div>

                            <Button
                                className="w-full h-11 text-sm font-semibold"
                                disabled={mengirim}
                                onClick={handleKirimBalasan}
                            >
                                <SendIcon className="h-4 w-4 mr-2" />
                                {mengirim ? 'Mengirim Balasan...' : 'Kirim Balasan & Simpan Status'}
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                {/* Kolom Kanan: Info Toko & Timeline Status */}
                <div className="space-y-6">
                    {/* Kartu Informasi Toko Pelapor */}
                    <Card>
                        <CardHeader className="pb-3 border-b">
                            <CardTitle className="text-base font-semibold flex items-center gap-2">
                                <StoreIcon className="h-4 w-4 text-primary" />
                                Info Toko Pelapor
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-6 space-y-4">
                            <div className="flex items-center gap-3">
                                <Avatar className="h-12 w-12 border">
                                    <AvatarFallback className="bg-primary/10 text-primary font-bold text-lg">
                                        {inisial(tiket.toko.namaToko)}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <p className="font-bold text-base">{tiket.toko.namaToko}</p>
                                    <Badge variant="outline" className="text-[10px] mt-0.5">
                                        {tiket.toko.jenisUsaha}
                                    </Badge>
                                </div>
                            </div>

                            <Separator />

                            <div className="space-y-3 text-sm">
                                <div>
                                    <p className="text-xs text-muted-foreground">Pemilik Toko</p>
                                    <p className="font-semibold text-foreground">{tiket.toko.nama}</p>
                                </div>

                                <div>
                                    <p className="text-xs text-muted-foreground">Email Kontak</p>
                                    <div className="flex items-center justify-between mt-0.5">
                                        <p className="font-mono text-xs text-foreground truncate max-w-[180px]">
                                            {tiket.toko.email}
                                        </p>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-7 w-7"
                                            onClick={() => salinEmail(tiket.toko.email)}
                                        >
                                            <CopyIcon className="h-3.5 w-3.5 text-muted-foreground" />
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            <Button
                                variant="outline"
                                size="sm"
                                className="w-full"
                                onClick={() => router.visit(`/pengguna/${tiket.toko.id}`)}
                            >
                                <UserIcon className="h-4 w-4 mr-2" /> Detail Pengguna Toko
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    );
}
