import type { ReactNode } from 'react';

interface PageHeaderProps {
    judul: string;
    keterangan?: string;
    aksi?: ReactNode;
}

export function PageHeader({ judul, keterangan, aksi }: PageHeaderProps) {
    return (
        /*
      Sejak topbar dihapus, blok inilah kepala halaman — judulnya naik satu
      tingkat supaya tetap jadi jangkar pandangan pertama, bukan sekadar
      teks pertama di dalam konten.
    */
        <div className="mb-6 flex flex-wrap items-start justify-between gap-3 md:mb-8">
            <div className="min-w-0">
                <h1 className="text-2xl font-semibold tracking-tight md:text-3xl">
                    {judul}
                </h1>
                {keterangan && (
                    <p className="mt-1.5 text-sm text-muted-foreground">
                        {keterangan}
                    </p>
                )}
            </div>
            {aksi && (
                <div className="flex shrink-0 items-center gap-2">{aksi}</div>
            )}
        </div>
    );
}
