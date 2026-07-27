import { Link } from '@inertiajs/react';
import { DownloadIcon } from 'lucide-react';
import { BadgeStatusEbook } from '@/components/shared/BadgeStatus';
import { Card } from '@/components/ui/card';
import { AksiEbookMenu } from '@/features/resep/AksiEbookMenu';
import type { AksiEbook } from '@/features/resep/AksiEbookMenu';
import { SampulEbook } from '@/features/resep/SampulEbook';
import { formatAngka, formatTanggal, formatUkuranFile } from '@/lib/format';
import { LABEL_KATEGORI_EBOOK } from '@/lib/konstanta';
import type { Ebook } from '@/types';

export function KartuEbook({ ebook, aksi }: { ebook: Ebook; aksi: AksiEbook }) {
    const terbit = ebook.status === 'TERBIT';

    return (
        <Card className="gap-0 overflow-hidden py-0 transition-shadow hover:shadow-md">
            <Link
                href={`/resep/${ebook.id}`}
                className="block aspect-[16/10] overflow-hidden"
            >
                <SampulEbook
                    kategori={ebook.kategori}
                    coverUrl={ebook.coverUrl}
                    judul={ebook.judul}
                />
            </Link>

            <div className="flex flex-1 flex-col gap-3 p-4">
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                        <Link
                            href={`/resep/${ebook.id}`}
                            className="line-clamp-2 font-medium hover:underline"
                        >
                            {ebook.judul}
                        </Link>
                        <p className="mt-1 text-xs text-muted-foreground">
                            {LABEL_KATEGORI_EBOOK[ebook.kategori]}
                        </p>
                    </div>

                    <AksiEbookMenu
                        ebook={ebook}
                        aksi={aksi}
                        className="-mt-1 -mr-1 size-8 shrink-0"
                    />
                </div>

                <p className="line-clamp-2 text-sm text-muted-foreground">
                    {ebook.deskripsi}
                </p>

                <div className="mt-auto flex flex-wrap items-center justify-between gap-2 pt-1">
                    <BadgeStatusEbook status={ebook.status} />
                    <span className="angka-tabular inline-flex items-center gap-1 text-xs text-muted-foreground">
                        <DownloadIcon className="size-3.5" />
                        {formatAngka(ebook.jumlahUnduhan)}
                    </span>
                </div>

                <p className="angka-tabular border-t pt-3 text-xs text-muted-foreground">
                    {ebook.jumlahHalaman
                        ? `${ebook.jumlahHalaman} halaman · `
                        : ''}
                    {ebook.ukuranFileBytes
                        ? `${formatUkuranFile(ebook.ukuranFileBytes)} · `
                        : ''}
                    {terbit && ebook.tanggalTerbit
                        ? `Terbit ${formatTanggal(ebook.tanggalTerbit)}`
                        : `Dibuat ${formatTanggal(ebook.tanggalDibuat)}`}
                </p>
            </div>
        </Card>
    );
}
