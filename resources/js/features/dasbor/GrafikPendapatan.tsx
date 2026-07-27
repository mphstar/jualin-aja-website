import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { EmptyState } from '@/components/shared/StateTabel';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import { Skeleton } from '@/components/ui/skeleton';
import { formatRupiah, formatRupiahRingkas } from '@/lib/format';
import type { TitikDeret } from '@/types';

const KONFIG = {
    nilai: { label: 'Pendapatan', color: 'var(--chart-2)' },
} satisfies ChartConfig;

export function GrafikPendapatan({
    data,
    memuat,
}: {
    data: TitikDeret[];
    memuat?: boolean;
}) {
    const puncak = Math.max(...data.map((d) => d.nilai), 1);
    const kosong = data.every((d) => d.nilai === 0);

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Pendapatan</CardTitle>
                <p className="text-sm text-muted-foreground">
                    12 bulan terakhir, dari pembayaran lunas
                </p>
            </CardHeader>
            <CardContent>
                {memuat ? (
                    <Skeleton className="h-56 w-full" />
                ) : kosong ? (
                    <EmptyState judul="Belum ada pendapatan" />
                ) : (
                    <ChartContainer config={KONFIG} className="h-56 w-full">
                        <AreaChart
                            data={data}
                            margin={{ left: 4, right: 8, top: 8 }}
                        >
                            <defs>
                                <linearGradient
                                    id="isiPendapatan"
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="5%"
                                        stopColor="var(--color-nilai)"
                                        stopOpacity={0.35}
                                    />
                                    <stop
                                        offset="95%"
                                        stopColor="var(--color-nilai)"
                                        stopOpacity={0.02}
                                    />
                                </linearGradient>
                            </defs>
                            <CartesianGrid vertical={false} />
                            <XAxis
                                dataKey="label"
                                tickLine={false}
                                axisLine={false}
                                tickMargin={8}
                            />
                            <YAxis
                                tickLine={false}
                                axisLine={false}
                                width={52}
                                domain={[0, Math.ceil(puncak * 1.15)]}
                                tickFormatter={(v: number) =>
                                    formatRupiahRingkas(v)
                                }
                            />
                            <ChartTooltip
                                content={
                                    <ChartTooltipContent
                                        formatter={(nilai) =>
                                            formatRupiah(Number(nilai))
                                        }
                                    />
                                }
                            />
                            <Area
                                dataKey="nilai"
                                type="monotone"
                                stroke="var(--color-nilai)"
                                strokeWidth={2}
                                fill="url(#isiPendapatan)"
                                isAnimationActive={false}
                            />
                        </AreaChart>
                    </ChartContainer>
                )}
            </CardContent>
        </Card>
    );
}
