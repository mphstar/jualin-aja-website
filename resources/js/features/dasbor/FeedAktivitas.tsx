import { Link } from '@inertiajs/react';
import { EmptyState } from '@/components/shared/StateTabel';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { ItemAktivitas } from '@/features/aktivitas/ItemAktivitas';
import type { LogAktivitas } from '@/types';

export function FeedAktivitas({
    data,
    memuat,
}: {
    data: LogAktivitas[];
    memuat?: boolean;
}) {
    return (
        <Card className="gap-0 py-0">
            <CardHeader className="flex flex-wrap items-center justify-between gap-2 border-b py-4">
                <CardTitle className="text-base">Aktivitas terbaru</CardTitle>
                <Button variant="outline" size="sm" asChild>
                    <Link href="/aktivitas">Lihat semua</Link>
                </Button>
            </CardHeader>

            <CardContent className="p-4">
                {memuat ? (
                    <div className="space-y-4">
                        {Array.from({ length: 5 }).map((_, i) => (
                            <div key={i} className="flex gap-3">
                                <Skeleton className="size-9 shrink-0 rounded-full" />
                                <div className="flex-1 space-y-2">
                                    <Skeleton className="h-4 w-32" />
                                    <Skeleton className="h-4 w-2/3" />
                                </div>
                            </div>
                        ))}
                    </div>
                ) : data.length === 0 ? (
                    <EmptyState judul="Belum ada aktivitas" />
                ) : (
                    data.map((log, i) => (
                        <ItemAktivitas
                            key={log.id}
                            log={log}
                            terakhir={i === data.length - 1}
                        />
                    ))
                )}
            </CardContent>
        </Card>
    );
}
