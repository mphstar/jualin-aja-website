import { router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { TiketData } from '@/lib/api/tiket';

export function buatKolomTiket(): ColumnDef<TiketData, unknown>[] {
    return [
        {
            accessorKey: 'nomorTiket',
            header: 'No. Tiket',
            cell: ({ row }) => (
                <span className="font-mono text-xs font-semibold text-primary">
                    {row.original.nomorTiket}
                </span>
            ),
        },
        {
            accessorKey: 'toko',
            header: 'Toko Pelapor',
            cell: ({ row }) => (
                <div>
                    <p className="font-semibold text-sm">{row.original.toko.namaToko}</p>
                    <p className="text-xs text-muted-foreground">{row.original.toko.nama} ({row.original.toko.email})</p>
                </div>
            ),
        },
        {
            accessorKey: 'jenis',
            header: 'Kategori',
            cell: ({ row }) => {
                const t = row.original;

                return (
                    <Badge
                        variant={
                            t.jenis === 'SARAN'
                                ? 'secondary'
                                : t.jenis === 'KOMPLAIN'
                                ? 'destructive'
                                : 'outline'
                        }
                    >
                        {t.jenisLabel}
                    </Badge>
                );
            },
        },
        {
            accessorKey: 'subjek',
            header: 'Subjek & Pesan',
            cell: ({ row }) => (
                <div className="max-w-[280px]">
                    <p className="font-medium text-sm truncate">{row.original.subjek}</p>
                    <p className="text-xs text-muted-foreground truncate">{row.original.pesan}</p>
                </div>
            ),
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => {
                const t = row.original;

                return (
                    <Badge
                        variant={
                            t.status === 'SELESAI'
                                ? 'default'
                                : t.status === 'DIPROSES'
                                ? 'secondary'
                                : t.status === 'TERBUKA'
                                ? 'destructive'
                                : 'outline'
                        }
                    >
                        {t.statusLabel}
                    </Badge>
                );
            },
        },
        {
            accessorKey: 'dibuatPada',
            header: 'Tanggal Dibuat',
            cell: ({ row }) => (
                <span className="text-xs text-muted-foreground whitespace-nowrap">
                    {new Date(row.original.dibuatPada).toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                    })}
                </span>
            ),
        },
        {
            id: 'aksi',
            cell: ({ row }) => (
                <div className="text-right">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={(e) => {
                            e.stopPropagation();
                            router.visit(`/tiket/${row.original.id}`);
                        }}
                    >
                        Detail & Respon
                    </Button>
                </div>
            ),
        },
    ];
}
