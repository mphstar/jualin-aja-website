import {
    BadgeSisaHari,
    BadgeStatusLangganan,
} from '@/components/shared/BadgeStatus';
import { SelPengguna } from '@/components/shared/SelPengguna';
import { AksiPengguna } from '@/features/pengguna/AksiPengguna';
import type { OpsiKolom } from '@/features/pengguna/AksiPengguna';
import { formatTanggal } from '@/lib/format';
import { LABEL_DURASI, LABEL_JENIS_USAHA } from '@/lib/konstanta';
import type { PosUserRingkas } from '@/types';

/** Bentuk satu baris tabel pengguna untuk layar ponsel. */
export function KartuPengguna({
    user,
    opsi,
}: {
    user: PosUserRingkas;
    opsi: OpsiKolom;
}) {
    return (
        <div className="space-y-3">
            <div className="flex items-start justify-between gap-2">
                <SelPengguna nama={user.nama} namaToko={user.namaToko} />
                <AksiPengguna user={user} opsi={opsi} />
            </div>

            <div className="flex flex-wrap items-center gap-2">
                <BadgeStatusLangganan status={user.status} />
                <BadgeSisaHari
                    hari={user.sisaHari}
                    nonaktif={user.ditangguhkan}
                />
            </div>

            <dl className="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-muted-foreground">
                <div className="flex justify-between gap-2">
                    <dt>Paket</dt>
                    <dd className="text-foreground">
                        {user.durasi ? LABEL_DURASI[user.durasi] : '—'}
                    </dd>
                </div>
                <div className="flex justify-between gap-2">
                    <dt>Kota</dt>
                    <dd className="truncate text-foreground">{user.kota}</dd>
                </div>
                <div className="flex justify-between gap-2">
                    <dt>Usaha</dt>
                    <dd className="truncate text-foreground">
                        {LABEL_JENIS_USAHA[user.jenisUsaha]}
                    </dd>
                </div>
                <div className="flex justify-between gap-2">
                    <dt>Terdaftar</dt>
                    <dd className="angka-tabular text-foreground">
                        {formatTanggal(user.tanggalDaftar)}
                    </dd>
                </div>
            </dl>
        </div>
    );
}
