import { Link } from '@inertiajs/react';
import {
    BanIcon,
    BookOpenIcon,
    ChevronRightIcon,
    CircleCheckIcon,
    CircleXIcon,
    LogInIcon,
    LogOutIcon,
    PencilIcon,
    PlusIcon,
    RotateCcwIcon,
    SendIcon,
    SettingsIcon,
    TicketPlusIcon,
    Trash2Icon,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { formatRelatif, formatWaktu } from '@/lib/format';
import { LABEL_AKSI } from '@/lib/konstanta';
import { cn } from '@/lib/utils';
import type { JenisAksi, LogAktivitas } from '@/types';

type Nada = 'netral' | 'sukses' | 'peringatan' | 'bahaya' | 'info';

const TAMPILAN: Record<JenisAksi, { ikon: LucideIcon; nada: Nada }> = {
    MASUK: { ikon: LogInIcon, nada: 'netral' },
    KELUAR: { ikon: LogOutIcon, nada: 'netral' },
    LANGGANAN_PERPANJANG: { ikon: TicketPlusIcon, nada: 'sukses' },
    USER_TANGGUHKAN: { ikon: BanIcon, nada: 'bahaya' },
    USER_PULIHKAN: { ikon: RotateCcwIcon, nada: 'sukses' },
    EBOOK_TAMBAH: { ikon: PlusIcon, nada: 'info' },
    EBOOK_UBAH: { ikon: PencilIcon, nada: 'info' },
    EBOOK_TERBITKAN: { ikon: SendIcon, nada: 'sukses' },
    EBOOK_JADIKAN_DRAF: { ikon: BookOpenIcon, nada: 'peringatan' },
    EBOOK_HAPUS: { ikon: Trash2Icon, nada: 'bahaya' },
    PEMBAYARAN_LUNAS: { ikon: CircleCheckIcon, nada: 'sukses' },
    PEMBAYARAN_GAGAL: { ikon: CircleXIcon, nada: 'bahaya' },
    PENGATURAN_UBAH: { ikon: SettingsIcon, nada: 'netral' },
};

const WARNA: Record<Nada, string> = {
    netral: 'bg-muted text-muted-foreground',
    sukses: 'bg-sukses-lembut text-sukses',
    peringatan: 'bg-peringatan-lembut text-peringatan',
    bahaya: 'bg-bahaya-lembut text-destructive',
    info: 'bg-info-lembut text-info',
};

/** Ke mana entri log ini menautkan (PRD §F7.4). */
function tautanAktivitas(log: LogAktivitas): string | null {
    if (!log.targetId) {
        return null;
    }

    switch (log.targetTipe) {
        case 'USER':
        case 'LANGGANAN':
            return `/pengguna/${log.targetId}`;
        case 'EBOOK':
            return `/resep/${log.targetId}`;
        case 'PEMBAYARAN':
            return `/pembayaran/${log.targetId}`;
        default:
            return null;
    }
}

export function ItemAktivitas({
    log,
    terakhir,
}: {
    log: LogAktivitas;
    terakhir?: boolean;
}) {
    const { ikon: Ikon, nada } = TAMPILAN[log.aksi];
    const tautan = tautanAktivitas(log);

    const isi = (
        <>
            <div className="relative flex flex-col items-center">
                <div
                    className={cn(
                        'flex size-9 shrink-0 items-center justify-center rounded-full',
                        WARNA[nada],
                    )}
                >
                    <Ikon className="size-4" />
                </div>
                {/* Garis penyambung timeline — tidak digambar di entri terakhir. */}
                {!terakhir && <div className="mt-1 w-px flex-1 bg-border" />}
            </div>

            <div className="min-w-0 flex-1 pb-6">
                <div className="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                    <span className="text-sm font-medium">
                        {LABEL_AKSI[log.aksi]}
                    </span>
                    <span
                        className="angka-tabular text-xs text-muted-foreground"
                        title={formatWaktu(log.waktu)}
                    >
                        {formatRelatif(log.waktu)}
                    </span>
                </div>
                <p className="mt-0.5 text-sm text-muted-foreground">
                    {log.deskripsi}
                </p>
                <p className="mt-1 text-xs text-muted-foreground">
                    oleh {log.aktorNama}
                </p>
            </div>

            {tautan && (
                <ChevronRightIcon className="mt-2 size-4 shrink-0 text-muted-foreground" />
            )}
        </>
    );

    if (tautan) {
        return (
            <Link
                href={tautan}
                className="-mx-2 flex gap-3 rounded-md px-2 py-1 transition-colors hover:bg-accent/50"
            >
                {isi}
            </Link>
        );
    }

    return <div className="-mx-2 flex gap-3 px-2 py-1">{isi}</div>;
}
