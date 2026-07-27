import { CheckIcon, MonitorIcon, MoonIcon, SunIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTemaStore } from '@/stores/temaStore';
import type { ModeTema } from '@/stores/temaStore';

const PILIHAN: { nilai: ModeTema; label: string; ikon: typeof SunIcon }[] = [
    { nilai: 'terang', label: 'Terang', ikon: SunIcon },
    { nilai: 'gelap', label: 'Gelap', ikon: MoonIcon },
    { nilai: 'sistem', label: 'Ikut Sistem', ikon: MonitorIcon },
];

export function TombolTema() {
    const tema = useTemaStore((s) => s.tema);
    const setTema = useTemaStore((s) => s.setTema);

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                {/* size-7 menyamakannya dengan SidebarTrigger di sebelahnya. */}
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-7 text-muted-foreground"
                    aria-label="Ganti tema tampilan"
                >
                    <SunIcon className="size-4 scale-100 rotate-0 transition-transform dark:scale-0 dark:-rotate-90" />
                    <MoonIcon className="absolute size-4 scale-0 rotate-90 transition-transform dark:scale-100 dark:rotate-0" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {PILIHAN.map((p) => (
                    <DropdownMenuItem
                        key={p.nilai}
                        onClick={() => setTema(p.nilai)}
                    >
                        <p.ikon className="size-4" />
                        <span className="flex-1">{p.label}</span>
                        {tema === p.nilai && <CheckIcon className="size-4" />}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
