import {
    BanknoteIcon,
    CalendarClockIcon,
    CircleCheckIcon,
    UsersIcon,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { PageHeader } from '@/components/shared/PageHeader';
import { StatCard } from '@/components/shared/StatCard';
import { ErrorState } from '@/components/shared/StateTabel';
import { FeedAktivitas } from '@/features/dasbor/FeedAktivitas';
import { GrafikKomposisiPaket } from '@/features/dasbor/GrafikKomposisiPaket';
import { GrafikPendaftaran } from '@/features/dasbor/GrafikPendaftaran';
import { GrafikPendapatan } from '@/features/dasbor/GrafikPendapatan';
import { TabelAkanBerakhir } from '@/features/dasbor/TabelAkanBerakhir';
import { DialogPerpanjang } from '@/features/langganan/DialogPerpanjang';
import type { TargetPerpanjang } from '@/features/langganan/DialogPerpanjang';
import { useAdmin } from '@/hooks/useAdmin';
import { formatAngka, formatRupiah } from '@/lib/format';
import { AMBANG_AKAN_BERAKHIR } from '@/lib/konstanta';
import { useDasborStore } from '@/stores/dasborStore';

export function HalamanDasbor() {
    const statistik = useDasborStore((s) => s.statistik);
    const deretPendaftaran = useDasborStore((s) => s.deretPendaftaran);
    const deretPendapatan = useDasborStore((s) => s.deretPendapatan);
    const komposisiPaket = useDasborStore((s) => s.komposisiPaket);
    const akanBerakhir = useDasborStore((s) => s.akanBerakhir);
    const aktivitasTerbaru = useDasborStore((s) => s.aktivitasTerbaru);
    const memuat = useDasborStore((s) => s.memuat);
    const error = useDasborStore((s) => s.error);
    const ambilSemua = useDasborStore((s) => s.ambilSemua);

    const admin = useAdmin();
    const [target, setTarget] = useState<TargetPerpanjang | null>(null);

    useEffect(() => {
        void ambilSemua();
    }, [ambilSemua]);

    if (error) {
        return (
            <>
                <PageHeader judul="Dasbor" />
                <div className="rounded-lg border">
                    <ErrorState
                        pesan={error}
                        onCobaLagi={() => void ambilSemua()}
                    />
                </div>
            </>
        );
    }

    const namaPanggilan = admin?.nama.split(' ')[0] ?? 'Admin';

    return (
        <>
            <PageHeader
                judul={`Halo, ${namaPanggilan}`}
                keterangan="Ringkasan kondisi langganan dan pendapatan platform Anda."
            />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    judul="Total user"
                    nilai={formatAngka(statistik.totalUser)}
                    ikon={UsersIcon}
                    delta={statistik.deltaUserPersen}
                    keterangan="vs sebelum bulan ini"
                    memuat={memuat}
                />
                <StatCard
                    judul="Langganan aktif"
                    nilai={formatAngka(statistik.langgananAktif)}
                    ikon={CircleCheckIcon}
                    delta={statistik.deltaAktifPersen}
                    keterangan="termasuk uji coba"
                    memuat={memuat}
                />
                <StatCard
                    judul={`Akan berakhir ≤${AMBANG_AKAN_BERAKHIR} hari`}
                    nilai={formatAngka(statistik.akanBerakhir)}
                    ikon={CalendarClockIcon}
                    nada="peringatan"
                    keterangan={`${formatAngka(statistik.kedaluwarsa)} sudah kedaluwarsa`}
                    memuat={memuat}
                />
                <StatCard
                    judul="Pendapatan bulan ini"
                    nilai={formatRupiah(statistik.pendapatanBulanIni)}
                    ikon={BanknoteIcon}
                    delta={statistik.deltaPendapatanPersen}
                    keterangan="vs bulan lalu"
                    memuat={memuat}
                />
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                <GrafikPendaftaran data={deretPendaftaran} memuat={memuat} />
                <GrafikPendapatan data={deretPendapatan} memuat={memuat} />
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <TabelAkanBerakhir
                        data={akanBerakhir}
                        memuat={memuat}
                        onPerpanjang={(u) =>
                            setTarget({
                                userId: u.id,
                                namaToko: u.namaToko,
                                langgananAktif: u.langgananAktif,
                            })
                        }
                    />
                </div>
                <GrafikKomposisiPaket data={komposisiPaket} memuat={memuat} />
            </div>

            <div className="mt-4">
                <FeedAktivitas data={aktivitasTerbaru} memuat={memuat} />
            </div>

            <DialogPerpanjang
                target={target}
                onTutup={() => setTarget(null)}
                onBerhasil={() => void ambilSemua()}
            />
        </>
    );
}
