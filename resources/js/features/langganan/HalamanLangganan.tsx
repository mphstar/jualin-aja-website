import { router } from '@inertiajs/react';
import { SearchIcon, TicketIcon } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { DataTable } from '@/components/shared/DataTable';
import { PageHeader } from '@/components/shared/PageHeader';
import { EmptyState } from '@/components/shared/StateTabel';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { DialogPerpanjang } from '@/features/langganan/DialogPerpanjang';
import type { TargetPerpanjang } from '@/features/langganan/DialogPerpanjang';
import { KartuLangganan } from '@/features/langganan/KartuLangganan';
import { buatKolomLangganan } from '@/features/langganan/kolomLangganan';
import { useDebounce } from '@/hooks/useDebounce';
import {
    DAFTAR_DURASI,
    LABEL_DURASI,
    LABEL_STATUS_LANGGANAN,
} from '@/lib/konstanta';
import { useLanggananStore } from '@/stores/langgananStore';
import type { LanggananRingkas, StatusLangganan } from '@/types';

/** Urutan tab mengikuti alur perhatian admin: yang mendesak lebih dulu. */
const URUTAN_TAB: (StatusLangganan | 'SEMUA')[] = [
    'SEMUA',
    'AKAN_BERAKHIR',
    'AKTIF',
    'TRIAL',
    'KEDALUWARSA',
    'NONAKTIF',
];

export function HalamanLangganan() {
    const daftar = useLanggananStore((s) => s.daftar);
    const jumlah = useLanggananStore((s) => s.jumlah);
    const memuat = useLanggananStore((s) => s.memuat);
    const error = useLanggananStore((s) => s.error);
    const filter = useLanggananStore((s) => s.filter);
    const setFilter = useLanggananStore((s) => s.setFilter);
    const ambilDaftar = useLanggananStore((s) => s.ambilDaftar);

    const [cari, setCari] = useState(filter.cari ?? '');
    const cariTertunda = useDebounce(cari, 350);

    const [target, setTarget] = useState<TargetPerpanjang | null>(null);

    useEffect(() => {
        setFilter({ cari: cariTertunda });
    }, [cariTertunda, setFilter]);

    const bukaPerpanjang = useCallback(
        (l: LanggananRingkas) =>
            setTarget({
                userId: l.userId,
                namaToko: l.namaToko,
                langgananAktif: l,
            }),
        [],
    );

    const kolom = useMemo(
        () => buatKolomLangganan({ onPerpanjang: bukaPerpanjang }),
        [bukaPerpanjang],
    );

    const toolbar = (
        <div className="space-y-3">
            <Tabs
                value={filter.status ?? 'SEMUA'}
                onValueChange={(v) =>
                    setFilter({ status: v as typeof filter.status })
                }
            >
                <div className="overflow-x-auto">
                    <TabsList>
                        {URUTAN_TAB.map((s) => (
                            <TabsTrigger key={s} value={s} className="gap-1.5">
                                {s === 'SEMUA'
                                    ? 'Semua'
                                    : LABEL_STATUS_LANGGANAN[s]}
                                <span className="angka-tabular text-xs text-muted-foreground">
                                    {jumlah[s]}
                                </span>
                            </TabsTrigger>
                        ))}
                    </TabsList>
                </div>
            </Tabs>

            <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                <div className="relative w-full sm:max-w-xs">
                    <SearchIcon className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={cari}
                        onChange={(e) => setCari(e.target.value)}
                        placeholder="Cari nama pemilik atau toko…"
                        className="pl-9"
                        aria-label="Cari langganan"
                    />
                </div>

                <Select
                    value={filter.durasi ?? 'SEMUA'}
                    onValueChange={(v) =>
                        setFilter({ durasi: v as typeof filter.durasi })
                    }
                >
                    <SelectTrigger className="w-full sm:w-[150px]">
                        <SelectValue placeholder="Paket" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="SEMUA">Semua paket</SelectItem>
                        {DAFTAR_DURASI.map((d) => (
                            <SelectItem key={d} value={d}>
                                {LABEL_DURASI[d]}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <div className="flex items-center gap-2 sm:ml-auto">
                    <Switch
                        id="termasuk-riwayat"
                        checked={filter.termasukRiwayat ?? false}
                        onCheckedChange={(v) =>
                            setFilter({ termasukRiwayat: v })
                        }
                    />
                    <Label
                        htmlFor="termasuk-riwayat"
                        className="font-normal text-muted-foreground"
                    >
                        Tampilkan siklus lama
                    </Label>
                </div>
            </div>
        </div>
    );

    return (
        <>
            <PageHeader
                judul="Manajemen Langganan"
                keterangan={
                    filter.termasukRiwayat
                        ? 'Seluruh siklus langganan, termasuk perpanjangan yang sudah lewat.'
                        : 'Satu baris per toko — siklus yang sedang berlaku saat ini.'
                }
            />

            <DataTable
                kolom={kolom}
                data={daftar}
                memuat={memuat}
                error={error}
                onCobaLagi={() => void ambilDaftar()}
                toolbar={toolbar}
                onKlikBaris={(l) => router.visit(`/pengguna/${l.userId}`)}
                kartu={(l) => (
                    <KartuLangganan
                        langganan={l}
                        onPerpanjang={bukaPerpanjang}
                    />
                )}
                kosong={
                    <EmptyState
                        ikon={<TicketIcon className="size-8" />}
                        judul="Tidak ada langganan"
                        keterangan="Tidak ada langganan yang cocok dengan filter saat ini."
                    />
                }
            />

            <DialogPerpanjang
                target={target}
                onTutup={() => setTarget(null)}
                onBerhasil={() => void ambilDaftar()}
            />
        </>
    );
}
