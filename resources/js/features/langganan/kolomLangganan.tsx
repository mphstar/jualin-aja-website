import type { ColumnDef } from '@tanstack/react-table';
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

interface OpsiKolom {
    onPerpanjang: (langganan: LanggananRingkas) => void;
}

export function buatKolomLangganan(
    opsi: OpsiKolom,
): ColumnDef<LanggananRingkas, unknown>[] {
    return [
        {
            accessorKey: 'namaToko',
            header: 'Toko',
            cell: ({ row }) => (
                <SelPengguna
                    nama={row.original.namaUser}
                    namaToko={row.original.namaToko}
                    className="max-w-[230px]"
                />
            ),
        },
        {
            accessorKey: 'durasi',
            header: 'Paket',
            enableSorting: false,
            cell: ({ row }) => (
                <span className="flex items-center gap-2 text-sm whitespace-nowrap">
                    {LABEL_DURASI[row.original.durasi]}
                    {!row.original.berlaku && (
                        <span className="rounded border bg-muted px-1.5 py-0.5 text-xs text-muted-foreground">
                            Riwayat
                        </span>
                    )}
                </span>
            ),
        },
        {
            accessorKey: 'sumber',
            header: 'Sumber',
            enableSorting: false,
            cell: ({ row }) => (
                <span className="text-sm whitespace-nowrap text-muted-foreground">
                    {LABEL_SUMBER_LANGGANAN[row.original.sumber]}
                </span>
            ),
        },
        {
            accessorKey: 'tanggalMulai',
            header: 'Mulai',
            cell: ({ row }) => (
                <span className="angka-tabular text-sm whitespace-nowrap text-muted-foreground">
                    {formatTanggal(row.original.tanggalMulai)}
                </span>
            ),
        },
        {
            accessorKey: 'tanggalBerakhir',
            header: 'Berakhir',
            cell: ({ row }) => (
                <span className="angka-tabular text-sm whitespace-nowrap">
                    {formatTanggal(row.original.tanggalBerakhir)}
                </span>
            ),
        },
        {
            accessorKey: 'status',
            header: 'Status',
            enableSorting: false,
            cell: ({ row }) => (
                <BadgeStatusLangganan status={row.original.status} />
            ),
        },
        {
            accessorKey: 'sisaHari',
            header: 'Sisa',
            cell: ({ row }) => (
                <BadgeSisaHari
                    hari={row.original.sisaHari}
                    nonaktif={row.original.status === 'NONAKTIF'}
                />
            ),
        },
        {
            id: 'aksi',
            header: '',
            enableSorting: false,
            cell: ({ row }) =>
                // Siklus lama tidak bisa diperpanjang — perpanjangan selalu
                // menempel pada siklus yang sedang berlaku.
                row.original.berlaku ? (
                    <div className="text-right">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={(e) => {
                                e.stopPropagation();
                                opsi.onPerpanjang(row.original);
                            }}
                        >
                            <TicketPlusIcon className="size-4" />
                            Perpanjang
                        </Button>
                    </div>
                ) : null,
        },
    ];
}
