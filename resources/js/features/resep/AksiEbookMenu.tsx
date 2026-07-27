import { Link } from '@inertiajs/react';
import {
    EyeOffIcon,
    MoreHorizontalIcon,
    PencilIcon,
    SendIcon,
    Trash2Icon,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Ebook } from '@/types';

export interface AksiEbook {
    onUbahStatus: (ebook: Ebook) => void;
    onHapus: (ebook: Ebook) => void;
}

/** Menu aksi ebook — dipakai kartu grid, baris tabel, dan kartu mobile. */
export function AksiEbookMenu({
    ebook,
    aksi,
    className,
}: {
    ebook: Ebook;
    aksi: AksiEbook;
    className?: string;
}) {
    const terbit = ebook.status === 'TERBIT';

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className={className ?? 'size-8'}
                    aria-label={`Aksi untuk ${ebook.judul}`}
                    onClick={(e) => e.stopPropagation()}
                >
                    <MoreHorizontalIcon className="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="end"
                onClick={(e) => e.stopPropagation()}
            >
                <DropdownMenuItem asChild>
                    <Link href={`/resep/${ebook.id}/ubah`}>
                        <PencilIcon className="size-4" />
                        Ubah
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => aksi.onUbahStatus(ebook)}>
                    {terbit ? (
                        <>
                            <EyeOffIcon className="size-4" />
                            Jadikan draf
                        </>
                    ) : (
                        <>
                            <SendIcon className="size-4" />
                            Terbitkan
                        </>
                    )}
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    variant="destructive"
                    onClick={() => aksi.onHapus(ebook)}
                >
                    <Trash2Icon className="size-4" />
                    Hapus
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
