import { Link, router } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    CheckCircleIcon,
    MessageSquareIcon,
    SendIcon,
    StoreIcon,
    UserIcon,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { PageHeader } from '@/components/shared/PageHeader';
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
import { Textarea } from '@/components/ui/textarea';
import { api, KesalahanApi } from '@/lib/api';
import type { Tiket } from './HalamanTiket';

export function HalamanDetailTiket({ id }: { id: string }) {
    const [tiket, setTiket] = useState<Tiket | null>(null);
    const [memuat, setMemuat] = useState(true);
    const [balasan, setBalasan] = useState('');
    const [status, setStatus] = useState<string>('SELESAI');
    const [mengirim, setMengirim] = useState(false);

    const muatDetail = async () => {
        setMemuat(true);
        try {
            const data = await api.get<Tiket>(`/v1/tiket/${id}`);
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
            toast.error('Balasan admin tidak boleh kosong.');
            return;
        }

        setMengirim(true);
        try {
            const res = await api.post<Tiket>(`/v1/tiket/${id}/respon`, {
                balasan: balasan.trim(),
                status: status,
            });
            setTiket(res);
            toast.success('Balasan admin berhasil dikirim & status tiket diperbarui.');
        } catch (e) {
            const pesan = e instanceof KesalahanApi ? e.message : 'Gagal mengirim balasan.';
            toast.error(pesan);
        } finally {
            setMengirim(false);
        }
    };

    if (memuat) {
        return (
            <div className="py-12 text-center text-muted-foreground">
                Memuat detail tiket...
            </div>
        );
    }

    if (!tiket) {
        return (
            <div className="py-12 text-center">
                <p className="text-muted-foreground mb-4">Tiket tidak ditemukan.</p>
                <Button variant="outline" onClick={() => router.visit('/tiket')}>
                    Kembali ke Daftar Tiket
                </Button>
            </div>
        );
    }

    return (
        <div className="space-y-6 max-w-4xl mx-auto">
            <div className="flex items-center gap-2">
                <Button variant="ghost" size="sm" onClick={() => router.visit('/tiket')}>
                    <ArrowLeftIcon className="h-4 w-4 mr-1" /> Kembali
                </Button>
            </div>

            <PageHeader
                judul={`Detail Tiket: ${tiket.nomorTiket}`}
                keterangan={`Dikirim pada ${new Date(tiket.dibuatPada).toLocaleString('id-ID')}`}
            />

            <div className="grid gap-6 md:grid-cols-3">
                {/* Kolom Kiri: Detail Pesan & Balasan */}
                <div className="md:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
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
                            <CardTitle className="text-xl mt-2">{tiket.subjek}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="p-4 rounded-lg bg-muted/50 border whitespace-pre-wrap text-sm leading-relaxed">
                                {tiket.pesan}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Form Respon Admin */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg flex items-center gap-2">
                                <MessageSquareIcon className="h-5 w-5 text-primary" />
                                Respon & Balasan Admin
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label htmlFor="status">Perbarui Status Tiket</Label>
                                <Select value={status} onValueChange={setStatus}>
                                    <SelectTrigger className="w-full mt-1.5">
                                        <SelectValue placeholder="Pilih Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="DIPROSES">Diproses (Sedang Ditangani)</SelectItem>
                                        <SelectItem value="SELESAI">Selesai (Masalah/Saran Ditanggapi)</SelectItem>
                                        <SelectItem value="DITUTUP">Ditutup (Arsip Tiket)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label htmlFor="balasan">Pesan Balasan Resmi Admin</Label>
                                <Textarea
                                    id="balasan"
                                    rows={5}
                                    placeholder="Tuliskan respon resmi, langkah penanganan, atau apresiasi saran di sini..."
                                    value={balasan}
                                    onChange={(e) => setBalasan(e.target.value)}
                                    className="mt-1.5"
                                />
                            </div>

                            {tiket.balasanAdmin && (
                                <div className="text-xs text-muted-foreground flex items-center gap-1.5 bg-muted p-2.5 rounded">
                                    <CheckCircleIcon className="h-4 w-4 text-emerald-600" />
                                    Terakhir dibalas oleh <span className="font-semibold">{tiket.adminNama || 'Admin'}</span> pada{' '}
                                    {tiket.dibalasPada ? new Date(tiket.dibalasPada).toLocaleString('id-ID') : '-'}
                                </div>
                            )}

                            <Button
                                className="w-full"
                                disabled={mengirim}
                                onClick={handleKirimBalasan}
                            >
                                <SendIcon className="h-4 w-4 mr-2" />
                                {mengirim ? 'Mengirim Balasan...' : 'Kirim Balasan & Simpan Status'}
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                {/* Kolom Kanan: Informasi Toko Pelapor */}
                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base flex items-center gap-2">
                                <StoreIcon className="h-4 w-4 text-primary" />
                                Info Toko Pelapor
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <div>
                                <p className="text-muted-foreground text-xs">Nama Toko</p>
                                <p className="font-semibold">{tiket.toko.namaToko}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Jenis Usaha</p>
                                <p className="font-medium">{tiket.toko.jenisUsaha}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Pemilik Toko</p>
                                <p className="font-medium">{tiket.toko.nama}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Email Kontak</p>
                                <p className="font-medium text-xs break-all">{tiket.toko.email}</p>
                            </div>

                            <div className="pt-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="w-full"
                                    onClick={() => router.visit(`/pengguna/${tiket.toko.id}`)}
                                >
                                    <UserIcon className="h-4 w-4 mr-1.5" /> Lihat Detail Pengguna
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    );
}
