import { ConstructionIcon } from 'lucide-react';
import { PageHeader } from '@/components/shared/PageHeader';

interface SegeraHadirProps {
    judul: string;
    keterangan: string;
    fase: string;
}

/**
 * Penampung sementara untuk halaman yang belum dikerjakan.
 * Setiap fase mengganti satu placeholder ini dengan halaman sungguhan,
 * sehingga navigasi tidak pernah menabrak 404 di tengah pengerjaan.
 */
export function SegeraHadir({ judul, keterangan, fase }: SegeraHadirProps) {
    return (
        <div>
            <PageHeader judul={judul} keterangan={keterangan} />
            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center">
                <ConstructionIcon className="size-8 text-muted-foreground" />
                <p className="mt-3 font-medium">Sedang dikerjakan</p>
                <p className="mt-1 text-sm text-muted-foreground">
                    Halaman ini dibangun pada fase {fase}.
                </p>
            </div>
        </div>
    );
}
