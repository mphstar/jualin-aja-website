import { router } from '@inertiajs/react';
import {
    AlertCircleIcon,
    CheckCircle2Icon,
    ClockIcon,
    MessageSquareIcon,
    SearchIcon,
    XIcon,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { DataTable } from '@/components/shared/DataTable';
import { PageHeader } from '@/components/shared/PageHeader';
import { StatCard } from '@/components/shared/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { buatKolomTiket } from '@/features/tiket/kolomTiket';
import { api, KesalahanApi } from '@/lib/api';
import type { TiketData } from '@/lib/api/tiket';

export function HalamanTiket() {
    const [daftar, setDaftar] = useState<TiketData[]>([]);
    const [memuat, setMemuat] = useState(true);
    const [cari, setCari] = useState('');
    const [jenisFilter, setJenisFilter] = useState<string>('SEMUA');
    const [statusFilter, setStatusFilter] = useState<string>('SEMUA');

    const kolom = useMemo(() => buatKolomTiket(), []);

    const muatData = async () => {
        setMemuat(true);
        try {
            const params: Record<string, string> = {};
            if (cari) params.cari = cari;
            if (jenisFilter !== 'SEMUA') params.jenis = jenisFilter;
            if (statusFilter !== 'SEMUA') params.status = statusFilter;

            const res = await api.tiket.ambilDaftarTiket(params);
            setDaftar(res.data);
        } catch (e) {
            const pesan = e instanceof KesalahanApi ? e.message : 'Gagal memuat daftar tiket.';
            toast.error(pesan);
        } finally {
            setMemuat(false);
        }
    };

    useEffect(() => {
        muatData();
    }, [cari, jenisFilter, statusFilter]);

    // Hitung ringkasan status
    const totalTiket = daftar.length;
    const tiketTerbuka = daftar.filter((t) => t.status === 'TERBUKA').length;
    const tiketDiproses = daftar.filter((t) => t.status === 'DIPROSES').length;
    const tiketSelesai = daftar.filter((t) => t.status === 'SELESAI').length;

    return (
        <div className="space-y-6">
            <PageHeader
                judul="Saran & Komplain"
                keterangan="Kelola masukan pengembangan, laporan bug, dan pertanyaan bantuan dari pengguna aplikasi POS."
            />

            {/* Stat Cards */}
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    judul="Total Laporan"
                    nilai={String(totalTiket)}
                    ikon={MessageSquareIcon}
                    keterangan="Masukan & kendala pengguna"
                    memuat={memuat}
                />
                <StatCard
                    judul="Terbuka"
                    nilai={String(tiketTerbuka)}
                    ikon={AlertCircleIcon}
                    nada={tiketTerbuka > 0 ? 'bahaya' : 'netral'}
                    keterangan="Belum ditangani admin"
                    memuat={memuat}
                />
                <StatCard
                    judul="Sedang Diproses"
                    nilai={String(tiketDiproses)}
                    ikon={ClockIcon}
                    nada="peringatan"
                    keterangan="Dalam tahap investigasi"
                    memuat={memuat}
                />
                <StatCard
                    judul="Tiket Selesai"
                    nilai={String(tiketSelesai)}
                    ikon={CheckCircle2Icon}
                    keterangan="Telah ditanggapi resmi"
                    memuat={memuat}
                />
            </div>

            {/* Data Table */}
            <DataTable
                kolom={kolom}
                data={daftar}
                memuat={memuat}
                onKlikBaris={(t) => router.visit(`/tiket/${t.id}`)}
                toolbar={
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pb-2">
                        <div className="relative flex-1 max-w-sm">
                            <SearchIcon className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Cari nomor tiket, toko, atau pesan..."
                                value={cari}
                                onChange={(e) => setCari(e.target.value)}
                                className="pl-9 pr-9"
                            />
                            {cari && (
                                <button
                                    onClick={() => setCari('')}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                >
                                    <XIcon className="h-4 w-4" />
                                </button>
                            )}
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Select value={jenisFilter} onValueChange={setJenisFilter}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Kategori Tiket" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="SEMUA">Semua Kategori</SelectItem>
                                    <SelectItem value="SARAN">Saran Pengembangan</SelectItem>
                                    <SelectItem value="KOMPLAIN">Komplain / Bug</SelectItem>
                                    <SelectItem value="PERTANYAAN">Pertanyaan</SelectItem>
                                </SelectContent>
                            </Select>

                            <Select value={statusFilter} onValueChange={setStatusFilter}>
                                <SelectTrigger className="w-[160px]">
                                    <SelectValue placeholder="Status Tiket" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="SEMUA">Semua Status</SelectItem>
                                    <SelectItem value="TERBUKA">Terbuka</SelectItem>
                                    <SelectItem value="DIPROSES">Diproses</SelectItem>
                                    <SelectItem value="SELESAI">Selesai</SelectItem>
                                    <SelectItem value="DITUTUP">Ditutup</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                }
                kartu={(t) => (
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <span className="font-mono text-xs font-semibold text-primary">
                                {t.nomorTiket}
                            </span>
                            <Badge
                                variant={
                                    t.status === 'SELESAI'
                                        ? 'default'
                                        : t.status === 'DIPROSES'
                                        ? 'secondary'
                                        : t.status === 'TERBUKA'
                                        ? 'destructive'
                                        : 'outline'
                                }
                            >
                                {t.statusLabel}
                            </Badge>
                        </div>
                        <p className="font-semibold text-sm">{t.subjek}</p>
                        <p className="text-xs text-muted-foreground line-clamp-2">{t.pesan}</p>
                        <div className="flex items-center justify-between text-xs text-muted-foreground pt-1">
                            <span>{t.toko.namaToko} ({t.toko.nama})</span>
                            <Badge variant="outline" className="text-[10px]">
                                {t.jenisLabel}
                            </Badge>
                        </div>
                    </div>
                )}
            />
        </div>
    );
}
