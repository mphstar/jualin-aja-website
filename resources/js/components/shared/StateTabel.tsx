import { InboxIcon, RefreshCwIcon, TriangleAlertIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';

export function EmptyState({
    judul = 'Belum ada data',
    keterangan,
    ikon,
    aksi,
}: {
    judul?: string;
    keterangan?: string;
    ikon?: ReactNode;
    aksi?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center justify-center px-6 py-14 text-center">
            <div className="text-muted-foreground">
                {ikon ?? <InboxIcon className="size-8" />}
            </div>
            <p className="mt-3 font-medium">{judul}</p>
            {keterangan && (
                <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                    {keterangan}
                </p>
            )}
            {aksi && <div className="mt-4">{aksi}</div>}
        </div>
    );
}

export function ErrorState({
    pesan,
    onCobaLagi,
}: {
    pesan: string;
    onCobaLagi?: () => void;
}) {
    return (
        <div className="flex flex-col items-center justify-center px-6 py-14 text-center">
            <TriangleAlertIcon className="size-8 text-destructive" />
            <p className="mt-3 font-medium">Gagal memuat data</p>
            <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                {pesan}
            </p>
            {onCobaLagi && (
                <Button variant="outline" className="mt-4" onClick={onCobaLagi}>
                    <RefreshCwIcon className="size-4" />
                    Coba lagi
                </Button>
            )}
        </div>
    );
}
