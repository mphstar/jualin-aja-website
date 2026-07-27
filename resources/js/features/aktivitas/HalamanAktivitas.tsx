import { HistoryIcon, Loader2Icon, SearchIcon, XIcon } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { PageHeader } from '@/components/shared/PageHeader';
import { EmptyState, ErrorState } from '@/components/shared/StateTabel';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { ItemAktivitas } from '@/features/aktivitas/ItemAktivitas';
import { useDebounce } from '@/hooks/useDebounce';
import { formatAngka } from '@/lib/format';
import { DAFTAR_AKSI, LABEL_AKSI } from '@/lib/konstanta';
import { useAktivitasStore } from '@/stores/aktivitasStore';
import type { LogAktivitas } from '@/types';

function akhirHari(nilai: string): string | undefined {
    return nilai ? `${nilai}T23:59:59.999Z` : undefined;
}

/** Judul kelompok harian: "Hari ini" · "Kemarin" · "24 Jul 2026". */
function labelHari(iso: string): string {
    const tanggal = new Date(iso);
    const hariIni = new Date();
    const beda = Math.round(
        (new Date(
            hariIni.getFullYear(),
            hariIni.getMonth(),
            hariIni.getDate(),
        ).getTime() -
            new Date(
                tanggal.getFullYear(),
                tanggal.getMonth(),
                tanggal.getDate(),
            ).getTime()) /
            86_400_000,
    );

    if (beda === 0) {
        return 'Hari ini';
    }

    if (beda === 1) {
        return 'Kemarin';
    }

    return tanggal.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function kelompokkanPerHari(daftar: LogAktivitas[]) {
    const kelompok: { label: string; entri: LogAktivitas[] }[] = [];

    for (const log of daftar) {
        const label = labelHari(log.waktu);
        const terakhir = kelompok[kelompok.length - 1];

        if (terakhir && terakhir.label === label) {
            terakhir.entri.push(log);
        } else {
            kelompok.push({ label, entri: [log] });
        }
    }

    return kelompok;
}

export function HalamanAktivitas() {
    const daftar = useAktivitasStore((s) => s.daftar);
    const total = useAktivitasStore((s) => s.total);
    const memuat = useAktivitasStore((s) => s.memuat);
    const memuatLagi = useAktivitasStore((s) => s.memuatLagi);
    const error = useAktivitasStore((s) => s.error);
    const filter = useAktivitasStore((s) => s.filter);
    const setFilter = useAktivitasStore((s) => s.setFilter);
    const resetFilter = useAktivitasStore((s) => s.resetFilter);
    const ambilDaftar = useAktivitasStore((s) => s.ambilDaftar);
    const muatLebihBanyak = useAktivitasStore((s) => s.muatLebihBanyak);

    const [cari, setCari] = useState(filter.cari ?? '');
    const cariTertunda = useDebounce(cari, 350);
    const [dari, setDari] = useState('');
    const [sampai, setSampai] = useState('');

    useEffect(() => {
        setFilter({ cari: cariTertunda });
    }, [cariTertunda, setFilter]);

    const kelompok = useMemo(() => kelompokkanPerHari(daftar), [daftar]);

    const adaFilter =
        (filter.cari ?? '') !== '' ||
        filter.aksi !== 'SEMUA' ||
        Boolean(filter.dari) ||
        Boolean(filter.sampai);

    function bersihkan() {
        setCari('');
        setDari('');
        setSampai('');
        resetFilter();
    }

    return (
        <>
            <PageHeader
                judul="Log Aktivitas"
                keterangan="Catatan seluruh tindakan yang dilakukan dari panel admin."
            />

            <div className="mb-4 flex flex-col gap-3">
                <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                    <div className="relative w-full sm:max-w-xs">
                        <SearchIcon className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={cari}
                            onChange={(e) => setCari(e.target.value)}
                            placeholder="Cari deskripsi atau nama toko…"
                            className="pl-9"
                            aria-label="Cari aktivitas"
                        />
                    </div>

                    <Select
                        value={filter.aksi ?? 'SEMUA'}
                        onValueChange={(v) =>
                            setFilter({ aksi: v as typeof filter.aksi })
                        }
                    >
                        <SelectTrigger className="w-full sm:w-[230px]">
                            <SelectValue placeholder="Jenis aksi" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="SEMUA">
                                Semua jenis aksi
                            </SelectItem>
                            {DAFTAR_AKSI.map((a) => (
                                <SelectItem key={a} value={a}>
                                    {LABEL_AKSI[a]}
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
                        htmlFor="aktivitas-dari"
                        className="font-normal text-muted-foreground"
                    >
                        Rentang tanggal
                    </Label>
                    <Input
                        id="aktivitas-dari"
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

            <Card>
                <CardContent>
                    {memuat && daftar.length === 0 ? (
                        <div className="space-y-6">
                            {Array.from({ length: 6 }).map((_, i) => (
                                <div key={i} className="flex gap-3">
                                    <Skeleton className="size-9 shrink-0 rounded-full" />
                                    <div className="flex-1 space-y-2">
                                        <Skeleton className="h-4 w-40" />
                                        <Skeleton className="h-4 w-3/4" />
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : error ? (
                        <ErrorState
                            pesan={error}
                            onCobaLagi={() => void ambilDaftar()}
                        />
                    ) : daftar.length === 0 ? (
                        <EmptyState
                            ikon={<HistoryIcon className="size-8" />}
                            judul={
                                adaFilter
                                    ? 'Tidak ada yang cocok'
                                    : 'Belum ada aktivitas'
                            }
                            keterangan={
                                adaFilter
                                    ? 'Coba longgarkan kata kunci, jenis aksi, atau rentang tanggalnya.'
                                    : 'Setiap tindakan yang Anda lakukan akan tercatat di sini.'
                            }
                            aksi={
                                adaFilter ? (
                                    <Button
                                        variant="outline"
                                        onClick={bersihkan}
                                    >
                                        Atur ulang filter
                                    </Button>
                                ) : undefined
                            }
                        />
                    ) : (
                        <>
                            {kelompok.map((grup) => (
                                <section key={grup.label} className="mb-2">
                                    <h2 className="mb-3 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                        {grup.label}
                                    </h2>
                                    {grup.entri.map((log, i) => (
                                        <ItemAktivitas
                                            key={log.id}
                                            log={log}
                                            terakhir={
                                                i === grup.entri.length - 1
                                            }
                                        />
                                    ))}
                                </section>
                            ))}

                            <div className="flex items-center justify-between gap-3 border-t pt-4">
                                <p className="text-sm text-muted-foreground">
                                    Menampilkan{' '}
                                    <span className="angka-tabular font-medium">
                                        {formatAngka(daftar.length)}
                                    </span>{' '}
                                    dari{' '}
                                    <span className="angka-tabular font-medium">
                                        {formatAngka(total)}
                                    </span>{' '}
                                    entri
                                </p>
                                {daftar.length < total && (
                                    <Button
                                        variant="outline"
                                        onClick={() => void muatLebihBanyak()}
                                        disabled={memuatLagi}
                                    >
                                        {memuatLagi && (
                                            <Loader2Icon className="size-4 animate-spin" />
                                        )}
                                        Muat lebih banyak
                                    </Button>
                                )}
                            </div>
                        </>
                    )}
                </CardContent>
            </Card>
        </>
    );
}
