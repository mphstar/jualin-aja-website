import {
    BadgeStatusPembayaran,
    BadgeTipePembayaran,
} from '@/components/shared/BadgeStatus';
import { SelPengguna } from '@/components/shared/SelPengguna';
import { AksiPembayaran } from '@/features/pembayaran/AksiPembayaran';
import type { OpsiKolom } from '@/features/pembayaran/AksiPembayaran';
import { formatRupiah, formatTanggal } from '@/lib/format';
import { LABEL_METODE_PEMBAYARAN } from '@/lib/konstanta';
import { labelPaketPembayaran } from '@/lib/pembayaran';
import type { PembayaranRingkas } from '@/types';

export function KartuPembayaran({
    pembayaran: p,
    opsi,
}: {
    pembayaran: PembayaranRingkas;
    opsi: OpsiKolom;
}) {
    return (
        <div className="space-y-3">
            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <p className="angka-tabular font-medium">
                        {p.nomorInvoice}
                    </p>
                    <p className="angka-tabular text-xs text-muted-foreground">
                        {formatTanggal(p.tanggal)}
                    </p>
                </div>
                <AksiPembayaran pembayaran={p} opsi={opsi} />
            </div>

            <SelPengguna nama={p.namaUser} namaToko={p.namaToko} />

            <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="flex flex-wrap items-center gap-1.5">
                    <BadgeTipePembayaran tipe={p.tipe} />
                    <BadgeStatusPembayaran status={p.status} />
                </div>
                <span className="angka-tabular font-medium">
                    {formatRupiah(p.nominal)}
                </span>
            </div>

            <dl className="grid grid-cols-2 gap-x-4 text-xs text-muted-foreground">
                <div className="flex justify-between gap-2">
                    <dt>Paket / Konten</dt>
                    <dd className="text-foreground">
                        {labelPaketPembayaran(p)}
                    </dd>
                </div>
                <div className="flex justify-between gap-2">
                    <dt>Metode</dt>
                    <dd className="truncate text-foreground">
                        {LABEL_METODE_PEMBAYARAN[p.metode]}
                    </dd>
                </div>
            </dl>
        </div>
    );
}
