import { MonitorIcon, MoonIcon, SunIcon } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { useTemaStore } from '@/stores/temaStore';
import type { ModeTema } from '@/stores/temaStore';

const PILIHAN: { nilai: ModeTema; judul: string; ikon: LucideIcon }[] = [
    { nilai: 'terang', judul: 'Terang', ikon: SunIcon },
    { nilai: 'gelap', judul: 'Gelap', ikon: MoonIcon },
    { nilai: 'sistem', judul: 'Ikut sistem', ikon: MonitorIcon },
];

export function PilihanTema() {
    const tema = useTemaStore((s) => s.tema);
    const setTema = useTemaStore((s) => s.setTema);

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Tampilan</CardTitle>
                <p className="text-sm text-muted-foreground">
                    Pilihan ini tersimpan di peramban ini saja.
                </p>
            </CardHeader>

            <CardContent>
                <RadioGroup
                    value={tema}
                    onValueChange={(v) => setTema(v as ModeTema)}
                    className="grid gap-3 sm:grid-cols-3"
                >
                    {PILIHAN.map(({ nilai, judul, ikon: Ikon }) => (
                        <Label
                            key={nilai}
                            htmlFor={`tema-${nilai}`}
                            className="flex cursor-pointer items-center gap-3 rounded-md border p-3 font-normal transition-colors hover:bg-accent has-[[data-state=checked]]:border-primary"
                        >
                            <RadioGroupItem
                                value={nilai}
                                id={`tema-${nilai}`}
                            />
                            <Ikon className="size-4 text-muted-foreground" />
                            <span className="font-medium">{judul}</span>
                        </Label>
                    ))}
                </RadioGroup>
            </CardContent>
        </Card>
    );
}
