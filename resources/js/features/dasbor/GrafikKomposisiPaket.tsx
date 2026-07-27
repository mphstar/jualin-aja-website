import { useMemo } from 'react';
import { Cell, Pie, PieChart } from 'recharts';
import { EmptyState } from '@/components/shared/StateTabel';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import { Skeleton } from '@/components/ui/skeleton';
import { formatAngka } from '@/lib/format';
import { LABEL_DURASI } from '@/lib/konstanta';
import type { DurasiPaket, IrisanPaket } from '@/types';

const WARNA: Record<DurasiPaket, string> = {
    TRIAL: 'var(--chart-3)',
    BULANAN: 'var(--chart-1)',
    SEMESTERAN: 'var(--chart-2)',
    TAHUNAN: 'var(--chart-4)',
};

const KONFIG = {
    jumlah: { label: 'Toko' },
    TRIAL: { label: LABEL_DURASI.TRIAL, color: WARNA.TRIAL },
    BULANAN: { label: LABEL_DURASI.BULANAN, color: WARNA.BULANAN },
    SEMESTERAN: { label: LABEL_DURASI.SEMESTERAN, color: WARNA.SEMESTERAN },
    TAHUNAN: { label: LABEL_DURASI.TAHUNAN, color: WARNA.TAHUNAN },
} satisfies ChartConfig;

export function GrafikKomposisiPaket({
    data,
    memuat,
}: {
    data: IrisanPaket[];
    memuat?: boolean;
}) {
    const total = data.reduce((t, d) => t + d.jumlah, 0);

    // `nama` dipakai sebagai kunci agar ChartTooltipContent menemukan labelnya.
    const dataGrafik = useMemo(
        () =>
            data
                .filter((d) => d.jumlah > 0)
                .map((d) => ({ ...d, nama: d.durasi })),
        [data],
    );

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Komposisi paket</CardTitle>
                <p className="text-sm text-muted-foreground">
                    Berdasarkan langganan yang sedang berlaku
                </p>
            </CardHeader>
            <CardContent>
                {memuat ? (
                    <Skeleton className="h-56 w-full" />
                ) : total === 0 ? (
                    <EmptyState judul="Belum ada langganan" />
                ) : (
                    <div className="flex flex-col items-center gap-4 sm:flex-row">
                        <ChartContainer
                            config={KONFIG}
                            className="aspect-square h-44 shrink-0"
                        >
                            <PieChart>
                                <ChartTooltip
                                    content={
                                        <ChartTooltipContent
                                            nameKey="nama"
                                            hideLabel
                                        />
                                    }
                                />
                                <Pie
                                    data={dataGrafik}
                                    dataKey="jumlah"
                                    nameKey="nama"
                                    innerRadius="58%"
                                    outerRadius="88%"
                                    paddingAngle={2}
                                    isAnimationActive={false}
                                >
                                    {dataGrafik.map((d) => (
                                        <Cell
                                            key={d.durasi}
                                            fill={WARNA[d.durasi]}
                                        />
                                    ))}
                                </Pie>
                            </PieChart>
                        </ChartContainer>

                        <ul className="w-full space-y-2">
                            {data.map((d) => (
                                <li
                                    key={d.durasi}
                                    className="flex items-center gap-2 text-sm"
                                >
                                    <span
                                        className="size-2.5 shrink-0 rounded-full"
                                        style={{
                                            backgroundColor: WARNA[d.durasi],
                                        }}
                                    />
                                    <span className="flex-1">
                                        {LABEL_DURASI[d.durasi]}
                                    </span>
                                    <span className="angka-tabular font-medium">
                                        {formatAngka(d.jumlah)}
                                    </span>
                                    <span className="angka-tabular w-11 text-right text-xs text-muted-foreground">
                                        {total
                                            ? Math.round(
                                                  (d.jumlah / total) * 100,
                                              )
                                            : 0}
                                        %
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
