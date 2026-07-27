import { Link } from '@inertiajs/react';
import { SearchXIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';

export function HalamanTidakDitemukan() {
    return (
        <div className="flex flex-col items-center justify-center py-20 text-center">
            <SearchXIcon className="size-10 text-muted-foreground" />
            <h1 className="mt-4 text-2xl font-semibold tracking-tight">
                Halaman tidak ditemukan
            </h1>
            <p className="mt-2 max-w-sm text-sm text-muted-foreground">
                Alamat yang Anda buka tidak tersedia. Mungkin tautannya salah
                ketik atau halamannya sudah dipindahkan.
            </p>
            <Button asChild className="mt-6">
                <Link href="/dasbor">Kembali ke Dasbor</Link>
            </Button>
        </div>
    );
}
