import { Head, Link } from '@inertiajs/react';
import {
    LockIcon,
    SearchXIcon,
    ServerCrashIcon,
    WrenchIcon,
} from 'lucide-react';
import { denganShellBilaMasuk } from '@/components/layout/denganShell';
import { Button } from '@/components/ui/button';

/**
 * Halaman galat untuk 403/404/419/500/503 (lihat bootstrap/app.php).
 *
 * Dirender Inertia, bukan Blade, supaya pengguna tetap berada di dalam panel —
 * salah ketik alamat tidak melempar mereka keluar dari aplikasi.
 */
const PESAN = {
    403: {
        ikon: LockIcon,
        judul: 'Tidak punya akses',
        keterangan: 'Anda tidak berhak membuka halaman ini.',
    },
    404: {
        ikon: SearchXIcon,
        judul: 'Halaman tidak ditemukan',
        keterangan:
            'Alamat yang Anda buka tidak tersedia. Mungkin tautannya salah ketik atau halamannya sudah dipindahkan.',
    },
    419: {
        ikon: WrenchIcon,
        judul: 'Sesi kedaluwarsa',
        keterangan:
            'Halaman terlalu lama dibiarkan terbuka. Muat ulang lalu coba lagi.',
    },
    500: {
        ikon: ServerCrashIcon,
        judul: 'Terjadi kesalahan',
        keterangan:
            'Ada yang salah di sisi kami. Coba lagi beberapa saat lagi.',
    },
    503: {
        ikon: WrenchIcon,
        judul: 'Sedang dalam perawatan',
        keterangan: 'Layanan sedang diperbarui. Silakan kembali sebentar lagi.',
    },
} as const;

type KodeStatus = keyof typeof PESAN;

export default function Kesalahan({ status }: { status: number }) {
    const {
        ikon: Ikon,
        judul,
        keterangan,
    } = PESAN[status as KodeStatus] ?? PESAN[500];

    return (
        <>
            <Head title={judul} />
            <div className="flex flex-col items-center justify-center py-20 text-center">
                <Ikon className="size-10 text-muted-foreground" />
                <p className="mt-4 font-mono text-xs text-muted-foreground">
                    {status}
                </p>
                <h1 className="mt-1 text-2xl font-semibold tracking-tight">
                    {judul}
                </h1>
                <p className="mt-2 max-w-sm text-sm text-muted-foreground">
                    {keterangan}
                </p>
                <Button asChild className="mt-6">
                    <Link href="/dasbor">Kembali ke Dasbor</Link>
                </Button>
            </div>
        </>
    );
}

Kesalahan.layout = denganShellBilaMasuk;
