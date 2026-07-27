import type { LucideIcon } from 'lucide-react';
import { TrendingDownIcon, TrendingUpIcon } from 'lucide-react';
import { IkonKotak } from '@/components/shared/IkonKotak';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { formatPersen } from '@/lib/format';
import { cn } from '@/lib/utils';

interface StatCardProps {
    judul: string;
    nilai: string;
    ikon: LucideIcon;
    delta?: number;
    keterangan?: string;
    nada?: 'netral' | 'peringatan' | 'bahaya';
    memuat?: boolean;
}

export function StatCard({
    judul,
    nilai,
    ikon: Ikon,
    delta,
    keterangan,
    nada = 'netral',
    memuat,
}: StatCardProps) {
    if (memuat) {
        return (
            <Card>
                <CardContent className="space-y-3">
                    <Skeleton className="h-4 w-24" />
                    <Skeleton className="h-8 w-32" />
                    <Skeleton className="h-3 w-28" />
                </CardContent>
            </Card>
        );
    }

    const naik = delta !== undefined && delta >= 0;

    return (
        <Card>
            <CardContent>
                <div className="flex items-start justify-between gap-3">
                    <p className="text-sm font-medium text-muted-foreground">
                        {judul}
                    </p>
                    <IkonKotak ikon={Ikon} nada={nada} ukuran="sm" />
                </div>

                <p className="angka-tabular mt-2 text-2xl font-semibold tracking-tight">
                    {nilai}
                </p>

                <div className="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground">
                    {delta !== undefined && (
                        <span
                            className={cn(
                                'inline-flex items-center gap-0.5 font-medium',
                                naik ? 'text-sukses' : 'text-destructive',
                            )}
                        >
                            {naik ? (
                                <TrendingUpIcon className="size-3.5" />
                            ) : (
                                <TrendingDownIcon className="size-3.5" />
                            )}
                            {formatPersen(delta)}
                        </span>
                    )}
                    {keterangan && <span>{keterangan}</span>}
                </div>
            </CardContent>
        </Card>
    );
}
