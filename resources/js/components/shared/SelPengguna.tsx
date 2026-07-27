import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { inisial } from '@/lib/format';

/** Sel tabel: avatar + nama pemilik + nama toko. Dipakai di beberapa modul. */
export function SelPengguna({
    nama,
    namaToko,
    className,
}: {
    nama: string;
    namaToko: string;
    className?: string;
}) {
    return (
        <div className={className}>
            <div className="flex items-center gap-2.5">
                <Avatar className="size-8">
                    <AvatarFallback className="text-xs">
                        {inisial(nama)}
                    </AvatarFallback>
                </Avatar>
                <div className="min-w-0">
                    <p className="truncate font-medium">{namaToko}</p>
                    <p className="truncate text-xs text-muted-foreground">
                        {nama}
                    </p>
                </div>
            </div>
        </div>
    );
}
