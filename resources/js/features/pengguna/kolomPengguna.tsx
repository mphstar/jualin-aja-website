import type { ColumnDef } from '@tanstack/react-table';
import {
    BadgeSisaHari,
    BadgeStatusLangganan,
} from '@/components/shared/BadgeStatus';
import { SelPengguna } from '@/components/shared/SelPengguna';
import { AksiPengguna } from '@/features/pengguna/AksiPengguna';
import type { OpsiKolom } from '@/features/pengguna/AksiPengguna';
import { formatTanggal } from '@/lib/format';
import { LABEL_DURASI, LABEL_JENIS_USAHA } from '@/lib/konstanta';
import type { PosUserRingkas } from '@/types';

export function buatKolomPengguna(
    opsi: OpsiKolom,
): ColumnDef<PosUserRingkas, unknown>[] {
    return [
        {
            accessorKey: 'namaToko',
            header: 'Toko',
            cell: ({ row }) => (
                <SelPengguna
                    nama={row.original.nama}
                    namaToko={row.original.namaToko}
                    className="max-w-[230px]"
                />
            ),
        },
        {
            accessorKey: 'jenisUsaha',
            header: 'Jenis Usaha',
            cell: ({ row }) => (
                <span className="text-sm text-muted-foreground">
                    {LABEL_JENIS_USAHA[row.original.jenisUsaha]}
                </span>
            ),
        },
        {
            accessorKey: 'kota',
            header: 'Kota',
            cell: ({ row }) => (
                <span className="text-sm whitespace-nowrap">
                    {row.original.kota}
                </span>
            ),
        },
        {
            accessorKey: 'tanggalDaftar',
            header: 'Terdaftar',
            cell: ({ row }) => (
                <span className="angka-tabular text-sm whitespace-nowrap text-muted-foreground">
                    {formatTanggal(row.original.tanggalDaftar)}
                </span>
            ),
        },
        {
            accessorKey: 'durasi',
            header: 'Paket',
            enableSorting: false,
            cell: ({ row }) => (
                <span className="text-sm whitespace-nowrap">
                    {row.original.durasi
                        ? LABEL_DURASI[row.original.durasi]
                        : '—'}
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
                    nonaktif={row.original.ditangguhkan}
                />
            ),
        },
        {
            id: 'aksi',
            header: '',
            enableSorting: false,
            cell: ({ row }) => (
                <div className="text-right">
                    <AksiPengguna user={row.original} opsi={opsi} />
                </div>
            ),
        },
    ];
}
