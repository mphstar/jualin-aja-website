import { Link, router } from '@inertiajs/react';
import {
    BookOpenIcon,
    LayoutGridIcon,
    ListIcon,
    PlusIcon,
    SearchIcon,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { BadgeStatusEbook } from '@/components/shared/BadgeStatus';
import { DataTable } from '@/components/shared/DataTable';
import { DialogKonfirmasi } from '@/components/shared/DialogKonfirmasi';
import { PageHeader } from '@/components/shared/PageHeader';
import { EmptyState, ErrorState } from '@/components/shared/StateTabel';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { AksiEbookMenu } from '@/features/resep/AksiEbookMenu';
import type { AksiEbook } from '@/features/resep/AksiEbookMenu';
import { KartuEbook } from '@/features/resep/KartuEbook';
import { buatKolomEbook } from '@/features/resep/kolomEbook';
import { SampulEbook } from '@/features/resep/SampulEbook';
import { useDebounce } from '@/hooks/useDebounce';
import { KesalahanApi } from '@/lib/api';
import { formatAngka } from '@/lib/format';
import {
    DAFTAR_KATEGORI_EBOOK,
    LABEL_KATEGORI_EBOOK,
    LABEL_STATUS_EBOOK,
} from '@/lib/konstanta';
import { useEbookStore } from '@/stores/ebookStore';
import type { Ebook, StatusEbook } from '@/types';

const DAFTAR_STATUS: StatusEbook[] = ['TERBIT', 'DRAF'];

/** Bentuk ringkas satu baris tabel ebook untuk layar ponsel. */
function BarisEbookRingkas({ ebook, aksi }: { ebook: Ebook; aksi: AksiEbook }) {
    return (
        <div className="flex gap-3">
            <div className="h-14 w-20 shrink-0 overflow-hidden rounded">
                <SampulEbook
                    kategori={ebook.kategori}
                    coverUrl={ebook.coverUrl}
                    judul={ebook.judul}
                    className="gap-0 p-1 [&>span]:hidden"
                />
            </div>
            <div className="min-w-0 flex-1">
                <Link
                    href={`/resep/${ebook.id}`}
                    className="line-clamp-2 font-medium hover:underline"
                >
                    {ebook.judul}
                </Link>
                <p className="mt-0.5 text-xs text-muted-foreground">
                    {LABEL_KATEGORI_EBOOK[ebook.kategori]} ·{' '}
                    <span className="angka-tabular">
                        {formatAngka(ebook.jumlahUnduhan)} unduhan
                    </span>
                </p>
                <div className="mt-2">
                    <BadgeStatusEbook status={ebook.status} />
                </div>
            </div>
            <AksiEbookMenu ebook={ebook} aksi={aksi} />
        </div>
    );
}

export function HalamanKatalog() {
    const daftar = useEbookStore((s) => s.daftar);
    const memuat = useEbookStore((s) => s.memuat);
    const error = useEbookStore((s) => s.error);
    const filter = useEbookStore((s) => s.filter);
    const tampilan = useEbookStore((s) => s.tampilan);
    const setFilter = useEbookStore((s) => s.setFilter);
    const setTampilan = useEbookStore((s) => s.setTampilan);
    const ambilDaftar = useEbookStore((s) => s.ambilDaftar);
    const ubahStatus = useEbookStore((s) => s.ubahStatus);
    const hapus = useEbookStore((s) => s.hapus);

    const [cari, setCari] = useState(filter.cari ?? '');
    const cariTertunda = useDebounce(cari, 350);
    const [targetHapus, setTargetHapus] = useState<Ebook | null>(null);

    useEffect(() => {
        setFilter({ cari: cariTertunda });
    }, [cariTertunda, setFilter]);

    const aksi: AksiEbook = useMemo(
        () => ({
            onUbahStatus: async (ebook) => {
                const baru: StatusEbook =
                    ebook.status === 'TERBIT' ? 'DRAF' : 'TERBIT';

                try {
                    await ubahStatus(ebook.id, baru);
                    toast.success(
                        baru === 'TERBIT'
                            ? 'Ebook diterbitkan'
                            : 'Ebook jadi draf',
                        {
                            description:
                                baru === 'TERBIT'
                                    ? `"${ebook.judul}" kini bisa diunduh pelanggan berlangganan.`
                                    : `"${ebook.judul}" disembunyikan dari aplikasi POS.`,
                        },
                    );
                } catch (e) {
                    toast.error(
                        e instanceof KesalahanApi
                            ? e.message
                            : 'Gagal mengubah status.',
                    );
                }
            },
            onHapus: (ebook) => setTargetHapus(ebook),
        }),
        [ubahStatus],
    );

    const kolom = useMemo(() => buatKolomEbook(aksi), [aksi]);

    const adaFilter =
        (filter.cari ?? '') !== '' ||
        filter.kategori !== 'SEMUA' ||
        filter.status !== 'SEMUA';

    async function jalankanHapus() {
        if (!targetHapus) {
            return;
        }

        try {
            await hapus(targetHapus.id);
            toast.success('Ebook dihapus', {
                description: `"${targetHapus.judul}" tidak ada lagi di katalog.`,
            });
        } catch (e) {
            toast.error(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Gagal menghapus ebook.',
            );
        }
    }

    const toolbar = (
        <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
            <div className="relative w-full sm:max-w-xs">
                <SearchIcon className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    value={cari}
                    onChange={(e) => setCari(e.target.value)}
                    placeholder="Cari judul atau isi ebook…"
                    className="pl-9"
                    aria-label="Cari ebook"
                />
            </div>

            <Select
                value={filter.kategori ?? 'SEMUA'}
                onValueChange={(v) =>
                    setFilter({ kategori: v as typeof filter.kategori })
                }
            >
                <SelectTrigger className="w-full sm:w-[180px]">
                    <SelectValue placeholder="Kategori" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="SEMUA">Semua kategori</SelectItem>
                    {DAFTAR_KATEGORI_EBOOK.map((k) => (
                        <SelectItem key={k} value={k}>
                            {LABEL_KATEGORI_EBOOK[k]}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

            <Select
                value={filter.status ?? 'SEMUA'}
                onValueChange={(v) =>
                    setFilter({ status: v as typeof filter.status })
                }
            >
                <SelectTrigger className="w-full sm:w-[140px]">
                    <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="SEMUA">Semua status</SelectItem>
                    {DAFTAR_STATUS.map((s) => (
                        <SelectItem key={s} value={s}>
                            {LABEL_STATUS_EBOOK[s]}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

            <ToggleGroup
                type="single"
                value={tampilan}
                onValueChange={(v) => v && setTampilan(v as 'grid' | 'tabel')}
                variant="outline"
                className="sm:ml-auto"
            >
                <ToggleGroupItem value="grid" aria-label="Tampilan grid">
                    <LayoutGridIcon className="size-4" />
                </ToggleGroupItem>
                <ToggleGroupItem value="tabel" aria-label="Tampilan tabel">
                    <ListIcon className="size-4" />
                </ToggleGroupItem>
            </ToggleGroup>
        </div>
    );

    const kosong = (
        <EmptyState
            ikon={<BookOpenIcon className="size-8" />}
            judul={adaFilter ? 'Tidak ada yang cocok' : 'Katalog masih kosong'}
            keterangan={
                adaFilter
                    ? 'Coba longgarkan kata kunci atau filter yang dipakai.'
                    : 'Tambahkan ebook resep pertama untuk pelanggan berlangganan.'
            }
            aksi={
                <Button asChild>
                    <Link href="/resep/baru">
                        <PlusIcon className="size-4" />
                        Tambah ebook
                    </Link>
                </Button>
            }
        />
    );

    return (
        <>
            <PageHeader
                judul="Master Data Resep"
                keterangan="Katalog ebook resep yang bisa diunduh pelanggan dengan langganan aktif."
                aksi={
                    <Button onClick={() => router.visit('/resep/baru')}>
                        <PlusIcon className="size-4" />
                        Tambah ebook
                    </Button>
                }
            />

            {tampilan === 'tabel' ? (
                <DataTable
                    kolom={kolom}
                    data={daftar}
                    memuat={memuat}
                    error={error}
                    onCobaLagi={() => void ambilDaftar()}
                    toolbar={toolbar}
                    perHalamanAwal={25}
                    kosong={kosong}
                    kartu={(e) => <BarisEbookRingkas ebook={e} aksi={aksi} />}
                />
            ) : (
                <div className="space-y-3">
                    {toolbar}

                    {memuat ? (
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {Array.from({ length: 8 }).map((_, i) => (
                                <Card
                                    key={i}
                                    className="gap-0 overflow-hidden py-0"
                                >
                                    <Skeleton className="aspect-[16/10] rounded-none" />
                                    <div className="space-y-2 p-4">
                                        <Skeleton className="h-5 w-3/4" />
                                        <Skeleton className="h-3 w-1/3" />
                                        <Skeleton className="h-8 w-full" />
                                    </div>
                                </Card>
                            ))}
                        </div>
                    ) : error ? (
                        <div className="rounded-lg border">
                            <ErrorState
                                pesan={error}
                                onCobaLagi={() => void ambilDaftar()}
                            />
                        </div>
                    ) : daftar.length === 0 ? (
                        <div className="rounded-lg border">{kosong}</div>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {daftar.map((e) => (
                                <KartuEbook key={e.id} ebook={e} aksi={aksi} />
                            ))}
                        </div>
                    )}
                </div>
            )}

            <DialogKonfirmasi
                buka={targetHapus !== null}
                onTutup={() => setTargetHapus(null)}
                judul="Hapus ebook"
                keterangan={
                    <>
                        Ebook{' '}
                        <span className="font-medium text-foreground">
                            {targetHapus?.judul}
                        </span>{' '}
                        akan dihapus permanen beserta catatan unduhannya.
                        Tindakan ini tidak bisa dibatalkan.
                    </>
                }
                ketikUntukKonfirmasi={targetHapus?.judul}
                labelKonfirmasi="Hapus permanen"
                destruktif
                onKonfirmasi={jalankanHapus}
            />
        </>
    );
}
