import { TicketPlusIcon } from 'lucide-react';
import {
    BadgeSisaHari,
    BadgeStatusLangganan,
} from '@/components/shared/BadgeStatus';
import { SelPengguna } from '@/components/shared/SelPengguna';
import { Button } from '@/components/ui/button';
import { formatTanggal } from '@/lib/format';
import { LABEL_DURASI, LABEL_SUMBER_LANGGANAN } from '@/lib/konstanta';
import type { LanggananRingkas } from '@/types';

export function KartuLangganan({
    langganan,
    onPerpanjang,
}: {
    langganan: LanggananRingkas;
    onPerpanjang: (l: LanggananRingkas) => void;
}) {
    return (
        <div className="space-y-3">
            <SelPengguna
                nama={langganan.namaUser}
                namaToko={langganan.namaToko}
            />

            <div className="flex flex-wrap items-center gap-2">
                <BadgeStatusLangganan status={langganan.status} />
                <BadgeSisaHari
                    hari={langganan.sisaHari}
                    nonaktif={langganan.status === 'NONAKTIF'}
                />
                {!langganan.berlaku && (
                    <span className="rounded border bg-muted px-1.5 py-0.5 text-xs text-muted-foreground">
                        Riwayat
                    </span>
                )}
            </div>

            <dl className="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-muted-foreground">
                <div className="flex justify-between gap-2">
                    <dt>Paket</dt>
                    <dd className="text-foreground">
                        {LABEL_DURASI[langganan.durasi]}
                    </dd>
                </div>
                <div className="flex justify-between gap-2">
                    <dt>Sumber</dt>
                    <dd className="truncate text-foreground">
                        {LABEL_SUMBER_LANGGANAN[langganan.sumber]}
                    </dd>
                </div>
                <div className="flex justify-between gap-2">
                    <dt>Mulai</dt>
                    <dd className="angka-tabular text-foreground">
                        {formatTanggal(langganan.tanggalMulai)}
                    </dd>
                </div>
                <div className="flex justify-between gap-2">
                    <dt>Berakhir</dt>
                    <dd className="angka-tabular text-foreground">
                        {formatTanggal(langganan.tanggalBerakhir)}
                    </dd>
                </div>
            </dl>

            {langganan.berlaku && (
                <Button
                    variant="outline"
                    size="sm"
                    className="w-full"
                    onClick={(e) => {
                        e.stopPropagation();
                        onPerpanjang(langganan);
                    }}
                >
                    <TicketPlusIcon className="size-4" />
                    Perpanjang
                </Button>
            )}
        </div>
    );
}
