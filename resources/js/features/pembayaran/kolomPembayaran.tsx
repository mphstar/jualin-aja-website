import { Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { BadgeStatusPembayaran } from '@/components/shared/BadgeStatus';
import { SelPengguna } from '@/components/shared/SelPengguna';
import { AksiPembayaran } from '@/features/pembayaran/AksiPembayaran';
import type { OpsiKolom } from '@/features/pembayaran/AksiPembayaran';
import { formatRupiah, formatTanggal } from '@/lib/format';
import { LABEL_DURASI, LABEL_METODE_PEMBAYARAN } from '@/lib/konstanta';
import type { PembayaranRingkas } from '@/types';

export function buatKolomPembayaran(
    opsi: OpsiKolom,
): ColumnDef<PembayaranRingkas, unknown>[] {
    return [
        {
            accessorKey: 'nomorInvoice',
            header: 'Invoice',
            enableSorting: false,
            cell: ({ row }) => (
                <Link
                    href={`/pembayaran/${row.original.id}`}
                    className="angka-tabular font-medium whitespace-nowrap hover:underline"
                    onClick={(e) => e.stopPropagation()}
                >
                    {row.original.nomorInvoice}
                </Link>
            ),
        },
        {
            accessorKey: 'namaToko',
            header: 'Toko',
            cell: ({ row }) => (
                <SelPengguna
                    nama={row.original.namaUser}
                    namaToko={row.original.namaToko}
                    className="max-w-[220px]"
                />
            ),
        },
        {
            accessorKey: 'tanggal',
            header: 'Tanggal',
            cell: ({ row }) => (
                <span className="angka-tabular text-sm whitespace-nowrap text-muted-foreground">
                    {formatTanggal(row.original.tanggal)}
                </span>
            ),
        },
        {
            accessorKey: 'durasi',
            header: 'Paket',
            enableSorting: false,
            cell: ({ row }) => (
                <span className="text-sm whitespace-nowrap">
                    {LABEL_DURASI[row.original.durasi]}
                </span>
            ),
        },
        {
            accessorKey: 'metode',
            header: 'Metode',
            enableSorting: false,
            cell: ({ row }) => (
                <span className="text-sm whitespace-nowrap text-muted-foreground">
                    {LABEL_METODE_PEMBAYARAN[row.original.metode]}
                </span>
            ),
        },
        {
            accessorKey: 'status',
            header: 'Status',
            enableSorting: false,
            cell: ({ row }) => (
                <BadgeStatusPembayaran status={row.original.status} />
            ),
        },
        {
            accessorKey: 'nominal',
            header: 'Nominal',
            cell: ({ row }) => (
                <span className="angka-tabular font-medium whitespace-nowrap">
                    {formatRupiah(row.original.nominal)}
                </span>
            ),
        },
        {
            id: 'aksi',
            header: '',
            enableSorting: false,
            cell: ({ row }) => (
                <div className="text-right">
                    <AksiPembayaran pembayaran={row.original} opsi={opsi} />
                </div>
            ),
        },
    ];
}
