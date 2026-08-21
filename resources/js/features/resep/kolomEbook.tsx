import { Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { BadgeStatusEbook } from '@/components/shared/BadgeStatus';
import { AksiEbookMenu } from '@/features/resep/AksiEbookMenu';
import type { AksiEbook } from '@/features/resep/AksiEbookMenu';
import { SampulEbook } from '@/features/resep/SampulEbook';
import { formatAngka, formatTanggal, formatUkuranFile } from '@/lib/format';
import { labelKategoriKonten } from '@/lib/konstanta';
import type { Ebook } from '@/types';

export function buatKolomEbook(aksi: AksiEbook): ColumnDef<Ebook, unknown>[] {
    return [
        {
            accessorKey: 'judul',
            header: 'Ebook',
            cell: ({ row }) => (
                <div className="flex max-w-[320px] items-center gap-3">
                    <div className="h-10 w-14 shrink-0 overflow-hidden rounded">
                        <SampulEbook
                            jenis={row.original.jenis}
                            kategori={row.original.kategori}
                            kategoriPrompt={row.original.kategoriPrompt}
                            coverUrl={row.original.coverUrl}
                            judul={row.original.judul}
                            className="gap-0 p-1 [&>span]:hidden"
                        />
                    </div>
                    <div className="min-w-0">
                        <Link
                            href={`/resep/${row.original.id}`}
                            className="line-clamp-1 font-medium hover:underline"
                        >
                            {row.original.judul}
                        </Link>
                        <p className="text-xs text-muted-foreground">
                            {labelKategoriKonten(
                                row.original.jenis,
                                row.original.kategori,
                                row.original.kategoriPrompt,
                            )}
                        </p>
                    </div>
                </div>
            ),
        },
        {
            accessorKey: 'status',
            header: 'Status',
            enableSorting: false,
            cell: ({ row }) => (
                <BadgeStatusEbook status={row.original.status} />
            ),
        },
        {
            accessorKey: 'jumlahUnduhan',
            header: 'Unduhan',
            cell: ({ row }) => (
                <span className="angka-tabular text-sm">
                    {formatAngka(row.original.jumlahUnduhan)}
                </span>
            ),
        },
        {
            accessorKey: 'jumlahHalaman',
            header: 'Halaman',
            enableSorting: false,
            cell: ({ row }) => (
                <span className="angka-tabular text-sm text-muted-foreground">
                    {row.original.jumlahHalaman ?? '—'}
                </span>
            ),
        },
        {
            id: 'ukuran',
            header: 'Ukuran',
            enableSorting: false,
            cell: ({ row }) => (
                <span className="angka-tabular text-sm whitespace-nowrap text-muted-foreground">
                    {row.original.ukuranFileBytes
                        ? formatUkuranFile(row.original.ukuranFileBytes)
                        : '—'}
                </span>
            ),
        },
        {
            accessorKey: 'tanggalDibuat',
            header: 'Dibuat',
            cell: ({ row }) => (
                <span className="angka-tabular text-sm whitespace-nowrap text-muted-foreground">
                    {formatTanggal(row.original.tanggalDibuat)}
                </span>
            ),
        },
        {
            id: 'aksi',
            header: '',
            enableSorting: false,
            cell: ({ row }) => (
                <div className="text-right">
                    <AksiEbookMenu ebook={row.original} aksi={aksi} />
                </div>
            ),
        },
    ];
}
