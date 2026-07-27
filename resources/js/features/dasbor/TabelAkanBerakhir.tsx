import { Link } from '@inertiajs/react';
import { CircleCheckIcon, TicketPlusIcon } from 'lucide-react';
import { BadgeSisaHari } from '@/components/shared/BadgeStatus';
import { SelPengguna } from '@/components/shared/SelPengguna';
import { EmptyState } from '@/components/shared/StateTabel';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { AMBANG_AKAN_BERAKHIR } from '@/lib/konstanta';
import type { PosUserRingkas } from '@/types';

export function TabelAkanBerakhir({
    data,
    memuat,
    onPerpanjang,
}: {
    data: PosUserRingkas[];
    memuat?: boolean;
    onPerpanjang: (user: PosUserRingkas) => void;
}) {
    return (
        <Card className="gap-0 py-0">
            <CardHeader className="flex flex-wrap items-center justify-between gap-2 border-b py-4">
                <div>
                    <CardTitle className="text-base">Segera berakhir</CardTitle>
                    <p className="text-sm text-muted-foreground">
                        Langganan yang habis dalam {AMBANG_AKAN_BERAKHIR} hari
                        ke depan
                    </p>
                </div>
                <Button variant="outline" size="sm" asChild>
                    <Link href="/langganan">Lihat semua</Link>
                </Button>
            </CardHeader>

            <CardContent className="p-0">
                {memuat ? (
                    <div className="space-y-4 p-4">
                        {Array.from({ length: 4 }).map((_, i) => (
                            <Skeleton key={i} className="h-10 w-full" />
                        ))}
                    </div>
                ) : data.length === 0 ? (
                    <EmptyState
                        ikon={
                            <CircleCheckIcon className="size-8 text-sukses" />
                        }
                        judul="Tidak ada yang mendesak"
                        keterangan={`Tidak ada langganan yang berakhir dalam ${AMBANG_AKAN_BERAKHIR} hari ke depan.`}
                    />
                ) : (
                    <ul className="divide-y">
                        {data.map((u) => (
                            <li
                                key={u.id}
                                className="flex flex-wrap items-center gap-3 px-4 py-3"
                            >
                                <Link
                                    href={`/pengguna/${u.id}`}
                                    className="min-w-0 flex-1"
                                >
                                    <SelPengguna
                                        nama={u.nama}
                                        namaToko={u.namaToko}
                                    />
                                </Link>
                                <BadgeSisaHari hari={u.sisaHari} />
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => onPerpanjang(u)}
                                >
                                    <TicketPlusIcon className="size-4" />
                                    Perpanjang
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
