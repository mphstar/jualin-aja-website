import { router } from '@inertiajs/react';
import {
    BanknoteIcon,
    ClockIcon,
    ReceiptTextIcon,
    SearchIcon,
    XCircleIcon,
    XIcon,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { DataTable } from '@/components/shared/DataTable';
import { DialogKonfirmasi } from '@/components/shared/DialogKonfirmasi';
import { PageHeader } from '@/components/shared/PageHeader';
import { StatCard } from '@/components/shared/StatCard';
import { EmptyState } from '@/components/shared/StateTabel';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { OpsiKolom } from '@/features/pembayaran/AksiPembayaran';
import { KartuPembayaran } from '@/features/pembayaran/KartuPembayaran';
import { buatKolomPembayaran } from '@/features/pembayaran/kolomPembayaran';
import { useDebounce } from '@/hooks/useDebounce';
import { KesalahanApi } from '@/lib/api';
import { formatAngka, formatRupiah } from '@/lib/format';
import {
    DAFTAR_METODE_PEMBAYARAN,
    DAFTAR_STATUS_PEMBAYARAN,
    LABEL_DURASI,
    LABEL_METODE_PEMBAYARAN,
    LABEL_STATUS_PEMBAYARAN,
} from '@/lib/konstanta';
import { usePembayaranStore } from '@/stores/pembayaranStore';
import type { PembayaranRingkas } from '@/types';

/** `<input type="date">` memberi 'YYYY-MM-DD'; batas atas harus akhir hari. */
function akhirHari(nilai: string): string | undefined {
    return nilai ? `${nilai}T23:59:59.999Z` : undefined;
}

export function HalamanPembayaran() {
    const daftar = usePembayaranStore((s) => s.daftar);
    const ringkasan = usePembayaranStore((s) => s.ringkasan);
    const memuat = usePembayaranStore((s) => s.memuat);
    const error = usePembayaranStore((s) => s.error);
    const filter = usePembayaranStore((s) => s.filter);
    const setFilter = usePembayaranStore((s) => s.setFilter);
    const resetFilter = usePembayaranStore((s) => s.resetFilter);
    const ambilDaftar = usePembayaranStore((s) => s.ambilDaftar);
    const tandaiLunas = usePembayaranStore((s) => s.tandaiLunas);
    const tandaiGagal = usePembayaranStore((s) => s.tandaiGagal);

    const [cari, setCari] = useState(filter.cari ?? '');
    const cariTertunda = useDebounce(cari, 350);
    const [dari, setDari] = useState('');
    const [sampai, setSampai] = useState('');

    const [targetLunas, setTargetLunas] = useState<PembayaranRingkas | null>(
        null,
    );
    const [targetGagal, setTargetGagal] = useState<PembayaranRingkas | null>(
        null,
    );

    useEffect(() => {
        setFilter({ cari: cariTertunda });
    }, [cariTertunda, setFilter]);

    const opsiAksi: OpsiKolom = useMemo(
        () => ({
            onLihat: (p) => router.visit(`/pembayaran/${p.id}`),
            onTandaiLunas: (p) => setTargetLunas(p),
            onTandaiGagal: (p) => setTargetGagal(p),
        }),
        [],
    );

    const kolom = useMemo(() => buatKolomPembayaran(opsiAksi), [opsiAksi]);

    const adaFilter =
        (filter.cari ?? '') !== '' ||
        filter.status !== 'SEMUA' ||
        filter.metode !== 'SEMUA' ||
        Boolean(filter.dari) ||
        Boolean(filter.sampai);

    function bersihkan() {
        setCari('');
        setDari('');
        setSampai('');
        resetFilter();
    }

    async function jalankanLunas() {
        if (!targetLunas) {
            return;
        }

        try {
            await tandaiLunas(targetLunas.id);
            toast.success('Invoice ditandai lunas', {
                description: `Langganan ${targetLunas.namaToko} otomatis diperpanjang ${LABEL_DURASI[targetLunas.durasi]}.`,
            });
        } catch (e) {
            toast.error(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Gagal menandai invoice.',
            );
        }
    }

    async function jalankanGagal() {
        if (!targetGagal) {
            return;
        }

        try {
            await tandaiGagal(targetGagal.id);
            toast.success('Invoice ditandai gagal');
        } catch (e) {
            toast.error(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Gagal menandai invoice.',
            );
        }
    }

    const toolbar = (
        <div className="flex flex-col gap-3">
            <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                <div className="relative w-full sm:max-w-xs">
                    <SearchIcon className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={cari}
                        onChange={(e) => setCari(e.target.value)}
                        placeholder="Cari no. invoice atau toko…"
                        className="pl-9"
                        aria-label="Cari pembayaran"
                    />
                </div>

                <Select
                    value={filter.status ?? 'SEMUA'}
                    onValueChange={(v) =>
                        setFilter({ status: v as typeof filter.status })
                    }
                >
                    <SelectTrigger className="w-full sm:w-[150px]">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="SEMUA">Semua status</SelectItem>
                        {DAFTAR_STATUS_PEMBAYARAN.map((s) => (
                            <SelectItem key={s} value={s}>
                                {LABEL_STATUS_PEMBAYARAN[s]}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <Select
                    value={filter.metode ?? 'SEMUA'}
                    onValueChange={(v) =>
                        setFilter({ metode: v as typeof filter.metode })
                    }
                >
                    <SelectTrigger className="w-full sm:w-[180px]">
                        <SelectValue placeholder="Metode" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="SEMUA">Semua metode</SelectItem>
                        {DAFTAR_METODE_PEMBAYARAN.map((m) => (
                            <SelectItem key={m} value={m}>
                                {LABEL_METODE_PEMBAYARAN[m]}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                {adaFilter && (
                    <Button variant="ghost" onClick={bersihkan}>
                        <XIcon className="size-4" />
                        Atur ulang
                    </Button>
                )}
            </div>

            <div className="flex flex-wrap items-center gap-2">
                <Label
                    htmlFor="tanggal-dari"
                    className="font-normal text-muted-foreground"
                >
                    Rentang tanggal
                </Label>
                <Input
                    id="tanggal-dari"
                    type="date"
                    value={dari}
                    max={sampai || undefined}
                    onChange={(e) => {
                        setDari(e.target.value);
                        setFilter({ dari: e.target.value || undefined });
                    }}
                    className="w-[160px]"
                />
                <span className="text-sm text-muted-foreground">–</span>
                <Input
                    type="date"
                    value={sampai}
                    min={dari || undefined}
                    onChange={(e) => {
                        setSampai(e.target.value);
                        setFilter({ sampai: akhirHari(e.target.value) });
                    }}
                    className="w-[160px]"
                    aria-label="Tanggal sampai"
                />
            </div>
        </div>
    );

    return (
        <>
            <PageHeader
                judul="Riwayat Pembayaran"
                keterangan="Tagihan langganan beserta status pembayarannya."
            />

            <div className="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <StatCard
                    judul="Lunas bulan ini"
                    nilai={formatRupiah(ringkasan.totalLunasBulanIni)}
                    ikon={BanknoteIcon}
                    memuat={memuat && daftar.length === 0}
                />
                <StatCard
                    judul="Menunggu pembayaran"
                    nilai={formatAngka(ringkasan.jumlahMenunggu)}
                    ikon={ClockIcon}
                    nada="peringatan"
                    keterangan="invoice belum dibayar"
                    memuat={memuat && daftar.length === 0}
                />
                <StatCard
                    judul="Gagal"
                    nilai={formatAngka(ringkasan.jumlahGagal)}
                    ikon={XCircleIcon}
                    nada="bahaya"
                    keterangan="perlu ditindaklanjuti"
                    memuat={memuat && daftar.length === 0}
                />
            </div>

            <DataTable
                kolom={kolom}
                data={daftar}
                memuat={memuat}
                error={error}
                onCobaLagi={() => void ambilDaftar()}
                toolbar={toolbar}
                perHalamanAwal={25}
                onKlikBaris={(p) => router.visit(`/pembayaran/${p.id}`)}
                kartu={(p) => (
                    <KartuPembayaran pembayaran={p} opsi={opsiAksi} />
                )}
                kosong={
                    <EmptyState
                        ikon={<ReceiptTextIcon className="size-8" />}
                        judul={
                            adaFilter
                                ? 'Tidak ada yang cocok'
                                : 'Belum ada pembayaran'
                        }
                        keterangan={
                            adaFilter
                                ? 'Coba longgarkan kata kunci, filter, atau rentang tanggalnya.'
                                : 'Tagihan langganan akan muncul di sini.'
                        }
                        aksi={
                            adaFilter ? (
                                <Button variant="outline" onClick={bersihkan}>
                                    Atur ulang filter
                                </Button>
                            ) : undefined
                        }
                    />
                }
            />

            <DialogKonfirmasi
                buka={targetLunas !== null}
                onTutup={() => setTargetLunas(null)}
                judul="Tandai invoice lunas"
                keterangan={
                    <>
                        Invoice{' '}
                        <span className="font-medium text-foreground">
                            {targetLunas?.nomorInvoice}
                        </span>{' '}
                        akan ditandai lunas, dan langganan{' '}
                        <span className="font-medium text-foreground">
                            {targetLunas?.namaToko}
                        </span>{' '}
                        otomatis diperpanjang{' '}
                        {targetLunas ? LABEL_DURASI[targetLunas.durasi] : ''}.
                    </>
                }
                labelKonfirmasi="Tandai lunas"
                onKonfirmasi={jalankanLunas}
            />

            <DialogKonfirmasi
                buka={targetGagal !== null}
                onTutup={() => setTargetGagal(null)}
                judul="Tandai invoice gagal"
                keterangan={
                    <>
                        Invoice{' '}
                        <span className="font-medium text-foreground">
                            {targetGagal?.nomorInvoice}
                        </span>{' '}
                        ditandai gagal. Masa langganan tidak bertambah.
                    </>
                }
                labelKonfirmasi="Tandai gagal"
                destruktif
                onKonfirmasi={jalankanGagal}
            />
        </>
    );
}
