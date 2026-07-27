import {
    CheckCircle2Icon,
    EyeIcon,
    MoreHorizontalIcon,
    XCircleIcon,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { PembayaranRingkas } from '@/types';

export interface OpsiKolom {
    onLihat: (pembayaran: PembayaranRingkas) => void;
    onTandaiLunas: (pembayaran: PembayaranRingkas) => void;
    onTandaiGagal: (pembayaran: PembayaranRingkas) => void;
}

/** Menu aksi baris — dipakai tabel (desktop) dan kartu (mobile). */
export function AksiPembayaran({
    pembayaran: p,
    opsi,
}: {
    pembayaran: PembayaranRingkas;
    opsi: OpsiKolom;
}) {
    const bisaDiubah = p.status !== 'LUNAS';

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-8"
                    aria-label={`Aksi untuk ${p.nomorInvoice}`}
                    onClick={(e) => e.stopPropagation()}
                >
                    <MoreHorizontalIcon className="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="end"
                onClick={(e) => e.stopPropagation()}
            >
                <DropdownMenuItem onClick={() => opsi.onLihat(p)}>
                    <EyeIcon className="size-4" />
                    Lihat invoice
                </DropdownMenuItem>
                {bisaDiubah && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onClick={() => opsi.onTandaiLunas(p)}>
                            <CheckCircle2Icon className="size-4" />
                            Tandai lunas
                        </DropdownMenuItem>
                        {p.status !== 'GAGAL' && (
                            <DropdownMenuItem
                                variant="destructive"
                                onClick={() => opsi.onTandaiGagal(p)}
                            >
                                <XCircleIcon className="size-4" />
                                Tandai gagal
                            </DropdownMenuItem>
                        )}
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
