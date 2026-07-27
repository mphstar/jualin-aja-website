import {
    flexRender,
    getCoreRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useReactTable,
} from '@tanstack/react-table';
import type { ColumnDef, SortingState } from '@tanstack/react-table';
import {
    ArrowDownIcon,
    ArrowUpIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronsLeftIcon,
    ChevronsRightIcon,
    ChevronsUpDownIcon,
} from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { EmptyState, ErrorState } from '@/components/shared/StateTabel';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useLayarKecil } from '@/hooks/useMediaQuery';
import { cn } from '@/lib/utils';

export interface DataTableProps<T> {
    kolom: ColumnDef<T, unknown>[];
    data: T[];
    memuat?: boolean;
    error?: string | null;
    onCobaLagi?: () => void;
    /** Slot untuk kotak cari + filter milik tiap modul. */
    toolbar?: ReactNode;
    kosong?: ReactNode;
    onKlikBaris?: (baris: T) => void;
    perHalamanAwal?: number;
    /**
     * Tampilan satu baris sebagai kartu, dipakai di layar < 768px.
     * Tabel dengan 8 kolom tidak pernah nyaman di ponsel — scroll horizontal
     * menyembunyikan justru kolom aksi. Kalau tidak diisi, tabel tetap
     * dipakai dan hanya bisa digeser ke samping.
     */
    kartu?: (baris: T) => ReactNode;
    /**
     * Saklar untuk pindah ke paginasi sisi server nanti. Saat `true`,
     * tabel berhenti memotong data sendiri dan hanya menampilkan apa
     * yang diberikan — `lib/api` yang mengurus halaman.
     */
    paginasiManual?: boolean;
    totalBaris?: number;
}

export function DataTable<T>({
    kolom,
    data,
    memuat,
    error,
    onCobaLagi,
    toolbar,
    kosong,
    onKlikBaris,
    perHalamanAwal = 10,
    paginasiManual = false,
    totalBaris,
    kartu,
}: DataTableProps<T>) {
    const [sorting, setSorting] = useState<SortingState>([]);
    const layarKecil = useLayarKecil();
    const modeKartu = Boolean(kartu) && layarKecil;

    const tabel = useReactTable({
        data,
        columns: kolom,
        state: { sorting },
        onSortingChange: setSorting,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: paginasiManual ? undefined : getSortedRowModel(),
        getPaginationRowModel: paginasiManual
            ? undefined
            : getPaginationRowModel(),
        manualPagination: paginasiManual,
        manualSorting: paginasiManual,
        rowCount: paginasiManual ? totalBaris : undefined,
        initialState: { pagination: { pageSize: perHalamanAwal } },
    });

    const jumlahKolom = kolom.length;
    const barisTampil = tabel.getRowModel().rows;

    return (
        <div className="space-y-3">
            {toolbar}

            {modeKartu ? (
                <div className="space-y-3">
                    {memuat ? (
                        Array.from({ length: 4 }).map((_, i) => (
                            <div
                                key={`memuat-${i}`}
                                className="rounded-lg border p-4"
                            >
                                <Skeleton className="h-5 w-2/3" />
                                <Skeleton className="mt-2 h-4 w-1/3" />
                                <Skeleton className="mt-3 h-8 w-full" />
                            </div>
                        ))
                    ) : error ? (
                        <div className="rounded-lg border">
                            <ErrorState pesan={error} onCobaLagi={onCobaLagi} />
                        </div>
                    ) : barisTampil.length === 0 ? (
                        <div className="rounded-lg border">
                            {kosong ?? (
                                <EmptyState keterangan="Tidak ada baris yang cocok dengan filter saat ini." />
                            )}
                        </div>
                    ) : (
                        barisTampil.map((baris) => (
                            <div
                                key={baris.id}
                                onClick={
                                    onKlikBaris
                                        ? () => onKlikBaris(baris.original)
                                        : undefined
                                }
                                className={cn(
                                    'rounded-lg border p-4',
                                    onKlikBaris && 'cursor-pointer',
                                )}
                            >
                                {kartu!(baris.original)}
                            </div>
                        ))
                    )}
                </div>
            ) : (
                <div className="overflow-hidden rounded-lg border">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                {tabel.getHeaderGroups().map((grup) => (
                                    <TableRow
                                        key={grup.id}
                                        className="hover:bg-transparent"
                                    >
                                        {grup.headers.map((header) => {
                                            const bisaUrut =
                                                header.column.getCanSort();
                                            const arah =
                                                header.column.getIsSorted();

                                            return (
                                                <TableHead
                                                    key={header.id}
                                                    className="whitespace-nowrap"
                                                >
                                                    {header.isPlaceholder ? null : bisaUrut ? (
                                                        <button
                                                            type="button"
                                                            onClick={header.column.getToggleSortingHandler()}
                                                            className="-ml-1 inline-flex items-center gap-1 rounded px-1 py-0.5 transition-colors hover:text-foreground"
                                                        >
                                                            {flexRender(
                                                                header.column
                                                                    .columnDef
                                                                    .header,
                                                                header.getContext(),
                                                            )}
                                                            {arah === 'asc' ? (
                                                                <ArrowUpIcon className="size-3.5" />
                                                            ) : arah ===
                                                              'desc' ? (
                                                                <ArrowDownIcon className="size-3.5" />
                                                            ) : (
                                                                <ChevronsUpDownIcon className="size-3.5 opacity-40" />
                                                            )}
                                                        </button>
                                                    ) : (
                                                        flexRender(
                                                            header.column
                                                                .columnDef
                                                                .header,
                                                            header.getContext(),
                                                        )
                                                    )}
                                                </TableHead>
                                            );
                                        })}
                                    </TableRow>
                                ))}
                            </TableHeader>

                            <TableBody>
                                {memuat ? (
                                    Array.from({ length: 6 }).map((_, i) => (
                                        <TableRow key={`memuat-${i}`}>
                                            {Array.from({
                                                length: jumlahKolom,
                                            }).map((__, j) => (
                                                <TableCell key={`sel-${j}`}>
                                                    <Skeleton className="h-5 w-full" />
                                                </TableCell>
                                            ))}
                                        </TableRow>
                                    ))
                                ) : error ? (
                                    <TableRow className="hover:bg-transparent">
                                        <TableCell
                                            colSpan={jumlahKolom}
                                            className="p-0"
                                        >
                                            <ErrorState
                                                pesan={error}
                                                onCobaLagi={onCobaLagi}
                                            />
                                        </TableCell>
                                    </TableRow>
                                ) : barisTampil.length === 0 ? (
                                    <TableRow className="hover:bg-transparent">
                                        <TableCell
                                            colSpan={jumlahKolom}
                                            className="p-0"
                                        >
                                            {kosong ?? (
                                                <EmptyState keterangan="Tidak ada baris yang cocok dengan filter saat ini." />
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    barisTampil.map((baris) => (
                                        <TableRow
                                            key={baris.id}
                                            onClick={
                                                onKlikBaris
                                                    ? () =>
                                                          onKlikBaris(
                                                              baris.original,
                                                          )
                                                    : undefined
                                            }
                                            className={cn(
                                                onKlikBaris && 'cursor-pointer',
                                            )}
                                        >
                                            {baris
                                                .getVisibleCells()
                                                .map((sel) => (
                                                    <TableCell key={sel.id}>
                                                        {flexRender(
                                                            sel.column.columnDef
                                                                .cell,
                                                            sel.getContext(),
                                                        )}
                                                    </TableCell>
                                                ))}
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            )}

            {!memuat && !error && barisTampil.length > 0 && (
                <Paginasi tabel={tabel} />
            )}
        </div>
    );
}

function Paginasi<T>({
    tabel,
}: {
    tabel: ReturnType<typeof useReactTable<T>>;
}) {
    const { pageIndex, pageSize } = tabel.getState().pagination;
    const total = tabel.getRowCount();
    const dari = pageIndex * pageSize + 1;
    const sampai = Math.min((pageIndex + 1) * pageSize, total);

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 px-1">
            <p className="text-sm text-muted-foreground">
                Menampilkan{' '}
                <span className="angka-tabular font-medium">{dari}</span>–
                <span className="angka-tabular font-medium">{sampai}</span> dari{' '}
                <span className="angka-tabular font-medium">{total}</span> baris
            </p>

            <div className="flex items-center gap-4">
                <div className="flex items-center gap-2">
                    <span className="text-sm text-muted-foreground">Baris</span>
                    <Select
                        value={String(pageSize)}
                        onValueChange={(v) => tabel.setPageSize(Number(v))}
                    >
                        <SelectTrigger size="sm" className="w-[72px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {[10, 25, 50].map((n) => (
                                <SelectItem key={n} value={String(n)}>
                                    {n}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="flex items-center gap-1">
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        onClick={() => tabel.setPageIndex(0)}
                        disabled={!tabel.getCanPreviousPage()}
                        aria-label="Halaman pertama"
                    >
                        <ChevronsLeftIcon className="size-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        onClick={() => tabel.previousPage()}
                        disabled={!tabel.getCanPreviousPage()}
                        aria-label="Halaman sebelumnya"
                    >
                        <ChevronLeftIcon className="size-4" />
                    </Button>
                    <span className="angka-tabular px-2 text-sm">
                        {pageIndex + 1} / {Math.max(1, tabel.getPageCount())}
                    </span>
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        onClick={() => tabel.nextPage()}
                        disabled={!tabel.getCanNextPage()}
                        aria-label="Halaman berikutnya"
                    >
                        <ChevronRightIcon className="size-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        onClick={() =>
                            tabel.setPageIndex(tabel.getPageCount() - 1)
                        }
                        disabled={!tabel.getCanNextPage()}
                        aria-label="Halaman terakhir"
                    >
                        <ChevronsRightIcon className="size-4" />
                    </Button>
                </div>
            </div>
        </div>
    );
}
