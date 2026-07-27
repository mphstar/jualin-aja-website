import { CartesianGrid, Line, LineChart, XAxis, YAxis } from 'recharts';
import { EmptyState } from '@/components/shared/StateTabel';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import { Skeleton } from '@/components/ui/skeleton';
import type { TitikDeret } from '@/types';

const KONFIG = {
    nilai: { label: 'User baru', color: 'var(--chart-1)' },
} satisfies ChartConfig;

export function GrafikPendaftaran({
    data,
    memuat,
}: {
    data: TitikDeret[];
    memuat?: boolean;
}) {
    const puncak = Math.max(1, ...data.map((d) => d.nilai)) + 1;
    const kosong = data.every((d) => d.nilai === 0);

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">
                    Pendaftaran user baru
                </CardTitle>
                <p className="text-sm text-muted-foreground">
                    12 bulan terakhir
                </p>
            </CardHeader>
            <CardContent>
                {memuat ? (
                    <Skeleton className="h-56 w-full" />
                ) : kosong ? (
                    <EmptyState judul="Belum ada pendaftaran" />
                ) : (
                    <ChartContainer config={KONFIG} className="h-56 w-full">
                        <LineChart
                            data={data}
                            margin={{ left: 4, right: 8, top: 8 }}
                        >
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
                                width={28}
                                allowDecimals={false}
                                domain={[0, puncak]}
                            />
                            <ChartTooltip content={<ChartTooltipContent />} />
                            <Line
                                dataKey="nilai"
                                type="monotone"
                                stroke="var(--color-nilai)"
                                strokeWidth={2}
                                dot={false}
                                activeDot={{ r: 4 }}
                                isAnimationActive={false}
                            />
                        </LineChart>
                    </ChartContainer>
                )}
            </CardContent>
        </Card>
    );
}
