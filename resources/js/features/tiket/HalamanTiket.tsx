import { Link, router } from '@inertiajs/react';
import {
    MessageSquareIcon,
    SearchIcon,
    XIcon,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { PageHeader } from '@/components/shared/PageHeader';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { api, KesalahanApi } from '@/lib/api';
import type { TiketData } from '@/lib/api/tiket';

export function HalamanTiket() {
    const [daftar, setDaftar] = useState<TiketData[]>([]);
    const [memuat, setMemuat] = useState(true);
    const [cari, setCari] = useState('');
    const [jenisFilter, setJenisFilter] = useState<string>('SEMUA');
    const [statusFilter, setStatusFilter] = useState<string>('SEMUA');

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

    return (
        <div className="space-y-6">
            <PageHeader
                judul="Saran & Komplain"
                keterangan="Kelola masukan, laporan bug, dan pertanyaan bantuan dari pengguna aplikasi POS."
            />

            {/* Filter & Pencarian */}
            <Card>
                <CardContent className="p-4">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
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
                                    <SelectValue placeholder="Jenis Tiket" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="SEMUA">Semua Jenis</SelectItem>
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
                </CardContent>
            </Card>

            {/* Tabel Data Tiket */}
            <Card>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>No. Tiket</TableHead>
                                <TableHead>Toko Pelapor</TableHead>
                                <TableHead>Jenis</TableHead>
                                <TableHead>Subjek</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Dibuat</TableHead>
                                <TableHead className="text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {memuat ? (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                                        Memuat data tiket...
                                    </TableCell>
                                </TableRow>
                            ) : daftar.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-center py-12 text-muted-foreground">
                                        <MessageSquareIcon className="mx-auto h-8 w-8 mb-2 opacity-50" />
                                        Belum ada tiket saran atau komplain yang diterima.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                daftar.map((t) => (
                                    <TableRow key={t.id}>
                                        <TableCell className="font-mono font-medium">{t.nomorTiket}</TableCell>
                                        <TableCell>
                                            <div>
                                                <p className="font-semibold text-sm">{t.toko.namaToko}</p>
                                                <p className="text-xs text-muted-foreground">{t.toko.nama} ({t.toko.email})</p>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    t.jenis === 'SARAN'
                                                        ? 'secondary'
                                                        : t.jenis === 'KOMPLAIN'
                                                        ? 'destructive'
                                                        : 'outline'
                                                }
                                            >
                                                {t.jenisLabel}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="max-w-[240px] truncate font-medium">
                                            {t.subjek}
                                        </TableCell>
                                        <TableCell>
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
                                        </TableCell>
                                        <TableCell className="text-xs text-muted-foreground">
                                            {new Date(t.dibuatPada).toLocaleDateString('id-ID', {
                                                day: 'numeric',
                                                month: 'short',
                                                year: 'numeric',
                                            })}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => router.visit(`/tiket/${t.id}`)}
                                            >
                                                Detail & Balas
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
}
