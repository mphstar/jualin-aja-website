import {
    BanIcon,
    EyeIcon,
    MoreHorizontalIcon,
    RotateCcwIcon,
    TicketPlusIcon,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { PosUserRingkas } from '@/types';

export interface OpsiKolom {
    onLihat: (user: PosUserRingkas) => void;
    onPerpanjang: (user: PosUserRingkas) => void;
    onTangguhkan: (user: PosUserRingkas) => void;
    onPulihkan: (user: PosUserRingkas) => void;
}

/** Menu aksi baris — dipakai tabel (desktop) dan kartu (mobile). */
export function AksiPengguna({
    user,
    opsi,
}: {
    user: PosUserRingkas;
    opsi: OpsiKolom;
}) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-8"
                    aria-label={`Aksi untuk ${user.namaToko}`}
                    onClick={(e) => e.stopPropagation()}
                >
                    <MoreHorizontalIcon className="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="end"
                onClick={(e) => e.stopPropagation()}
            >
                <DropdownMenuItem onClick={() => opsi.onLihat(user)}>
                    <EyeIcon className="size-4" />
                    Lihat detail
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => opsi.onPerpanjang(user)}>
                    <TicketPlusIcon className="size-4" />
                    Perpanjang langganan
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                {user.ditangguhkan ? (
                    <DropdownMenuItem onClick={() => opsi.onPulihkan(user)}>
                        <RotateCcwIcon className="size-4" />
                        Pulihkan akun
                    </DropdownMenuItem>
                ) : (
                    <DropdownMenuItem
                        variant="destructive"
                        onClick={() => opsi.onTangguhkan(user)}
                    >
                        <BanIcon className="size-4" />
                        Tangguhkan akun
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
