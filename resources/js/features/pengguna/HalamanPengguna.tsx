import { router } from '@inertiajs/react';
import { PlusIcon, SearchIcon, UsersIcon, XIcon } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { DataTable } from '@/components/shared/DataTable';
import { DialogKonfirmasi } from '@/components/shared/DialogKonfirmasi';
import { PageHeader } from '@/components/shared/PageHeader';
import { EmptyState } from '@/components/shared/StateTabel';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { DialogPerpanjang } from '@/features/langganan/DialogPerpanjang';
import type { TargetPerpanjang } from '@/features/langganan/DialogPerpanjang';
import type { OpsiKolom } from '@/features/pengguna/AksiPengguna';
import { DialogTambahPengguna } from '@/features/pengguna/DialogTambahPengguna';
import { DialogTangguhkan } from '@/features/pengguna/DialogTangguhkan';
import { KartuPengguna } from '@/features/pengguna/KartuPengguna';
import { buatKolomPengguna } from '@/features/pengguna/kolomPengguna';
import { useDebounce } from '@/hooks/useDebounce';
import { KesalahanApi } from '@/lib/api';
import {
    DAFTAR_DURASI,
    DAFTAR_JENIS_USAHA,
    DAFTAR_STATUS_LANGGANAN,
    LABEL_DURASI,
    LABEL_JENIS_USAHA,
    LABEL_STATUS_LANGGANAN,
} from '@/lib/konstanta';
import { usePenggunaStore } from '@/stores/penggunaStore';
import type { PosUserRingkas } from '@/types';

export function HalamanPengguna() {
    // Pemilihan selektif — satu field per pemanggilan, supaya perubahan
    // di satu bagian store tidak me-render ulang seluruh halaman.
    const daftar = usePenggunaStore((s) => s.daftar);
    const memuat = usePenggunaStore((s) => s.memuat);
    const error = usePenggunaStore((s) => s.error);
    const filter = usePenggunaStore((s) => s.filter);
    const setFilter = usePenggunaStore((s) => s.setFilter);
    const resetFilter = usePenggunaStore((s) => s.resetFilter);
    const ambilDaftar = usePenggunaStore((s) => s.ambilDaftar);
    const tangguhkan = usePenggunaStore((s) => s.tangguhkan);
    const pulihkan = usePenggunaStore((s) => s.pulihkan);
    const tambahPengguna = usePenggunaStore((s) => s.tambahPengguna);

    const [cari, setCari] = useState(filter.cari ?? '');
    const cariTertunda = useDebounce(cari, 350);

    const [bukaTambah, setBukaTambah] = useState(false);
    const [targetPerpanjang, setTargetPerpanjang] =
        useState<TargetPerpanjang | null>(null);
    const [targetTangguhkan, setTargetTangguhkan] =
        useState<PosUserRingkas | null>(null);
    const [targetPulihkan, setTargetPulihkan] = useState<PosUserRingkas | null>(
        null,
    );

    // Pengambilan pertama sekaligus setiap kali kata kunci berubah.
    useEffect(() => {
        setFilter({ cari: cariTertunda });
    }, [cariTertunda, setFilter]);

    const opsiAksi: OpsiKolom = useMemo(
        () => ({
            onLihat: (u) => router.visit(`/pengguna/${u.id}`),
            onPerpanjang: (u) =>
                setTargetPerpanjang({
                    userId: u.id,
                    namaToko: u.namaToko,
                    langgananAktif: u.langgananAktif,
                }),
            onTangguhkan: (u) => setTargetTangguhkan(u),
            onPulihkan: (u) => setTargetPulihkan(u),
        }),
        [],
    );

    const kolom = useMemo(() => buatKolomPengguna(opsiAksi), [opsiAksi]);

    const adaFilter =
        (filter.cari ?? '') !== '' ||
        filter.status !== 'SEMUA' ||
        filter.durasi !== 'SEMUA' ||
        filter.jenisUsaha !== 'SEMUA';

    function bersihkan() {
        setCari('');
        resetFilter();
    }

    async function jalankanPulihkan() {
        if (!targetPulihkan) {
            return;
        }

        try {
            await pulihkan(targetPulihkan.id);
            toast.success('Akun dipulihkan', {
                description: `${targetPulihkan.namaToko} bisa mengakses aplikasi POS lagi.`,
            });
        } catch (e) {
            toast.error(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Gagal memulihkan akun.',
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
                    placeholder="Cari nama, toko, email, telepon…"
                    className="pl-9"
                    aria-label="Cari pengguna"
                />
            </div>

            <Select
                value={filter.status ?? 'SEMUA'}
                onValueChange={(v) =>
                    setFilter({ status: v as typeof filter.status })
                }
            >
                <SelectTrigger className="w-full sm:w-[170px]">
                    <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="SEMUA">Semua status</SelectItem>
                    {DAFTAR_STATUS_LANGGANAN.map((s) => (
                        <SelectItem key={s} value={s}>
                            {LABEL_STATUS_LANGGANAN[s]}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

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

            <Select
                value={filter.jenisUsaha ?? 'SEMUA'}
                onValueChange={(v) =>
                    setFilter({ jenisUsaha: v as typeof filter.jenisUsaha })
                }
            >
                <SelectTrigger className="w-full sm:w-[170px]">
                    <SelectValue placeholder="Jenis usaha" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="SEMUA">Semua jenis usaha</SelectItem>
                    {DAFTAR_JENIS_USAHA.map((j) => (
                        <SelectItem key={j} value={j}>
                            {LABEL_JENIS_USAHA[j]}
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
    );

    return (
        <>
            <PageHeader
                judul="Manajemen Pengguna"
                keterangan="Daftar pemilik toko yang memakai aplikasi POS beserta kondisi langganannya."
                aksi={
                    <Button onClick={() => setBukaTambah(true)}>
                        <PlusIcon className="mr-2 size-4" />
                        Tambah Pengguna
                    </Button>
                }
            />

            <DataTable
                kolom={kolom}
                data={daftar}
                memuat={memuat}
                error={error}
                onCobaLagi={() => void ambilDaftar()}
                toolbar={toolbar}
                onKlikBaris={(u) => router.visit(`/pengguna/${u.id}`)}
                kartu={(u) => <KartuPengguna user={u} opsi={opsiAksi} />}
                kosong={
                    <EmptyState
                        ikon={<UsersIcon className="size-8" />}
                        judul={
                            adaFilter
                                ? 'Tidak ada yang cocok'
                                : 'Belum ada pengguna'
                        }
                        keterangan={
                            adaFilter
                                ? 'Coba longgarkan kata kunci atau filter yang dipakai.'
                                : 'Pengguna akan muncul di sini setelah mendaftar lewat aplikasi POS atau ditambahkan admin.'
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

            <DialogTambahPengguna
                buka={bukaTambah}
                onTutup={() => setBukaTambah(false)}
                onKirim={tambahPengguna}
            />

            <DialogPerpanjang
                target={targetPerpanjang}
                onTutup={() => setTargetPerpanjang(null)}
                onBerhasil={() => void ambilDaftar()}
            />

            <DialogTangguhkan
                target={targetTangguhkan}
                onTutup={() => setTargetTangguhkan(null)}
                onKirim={tangguhkan}
            />

            <DialogKonfirmasi
                buka={targetPulihkan !== null}
                onTutup={() => setTargetPulihkan(null)}
                judul="Pulihkan akun"
                keterangan={
                    <>
                        <span className="font-medium text-foreground">
                            {targetPulihkan?.namaToko}
                        </span>{' '}
                        akan bisa mengakses aplikasi POS lagi sesuai masa
                        langganannya.
                    </>
                }
                labelKonfirmasi="Pulihkan"
                onKonfirmasi={jalankanPulihkan}
            />
        </>
    );
}
