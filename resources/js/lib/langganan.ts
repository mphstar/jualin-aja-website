import { addMonths, differenceInCalendarDays, parseISO } from 'date-fns';
import { AMBANG_AKAN_BERAKHIR, BULAN_PER_DURASI } from '@/lib/konstanta';
import type { DurasiPaket, Langganan, PosUser, StatusLangganan } from '@/types';

/**
 * Sisa hari sampai langganan berakhir.
 * Memakai selisih KALENDER (bukan jam), supaya "berakhir hari ini" stabil
 * berapa pun jam saat halaman dibuka.
 */
export function hitungSisaHari(
    tanggalBerakhir: string,
    sekarang: Date = new Date(),
): number {
    return differenceInCalendarDays(parseISO(tanggalBerakhir), sekarang);
}

/**
 * Sumber kebenaran TUNGGAL untuk status langganan (PRD §4.2).
 * Status tidak pernah disimpan — selalu diturunkan, supaya tidak bisa basi.
 *
 * Urutan penilaian penting:
 *   ditangguhkan → NONAKTIF  (override, menang atas semua)
 *   sudah lewat  → KEDALUWARSA
 *   ≤ 7 hari     → AKAN_BERAKHIR
 *   sumber trial → TRIAL
 *   selain itu   → AKTIF
 */
export function hitungStatusLangganan(
    langganan: Langganan | null | undefined,
    ditangguhkan: boolean,
    sekarang: Date = new Date(),
): StatusLangganan {
    if (ditangguhkan) {
        return 'NONAKTIF';
    }

    if (!langganan) {
        return 'KEDALUWARSA';
    }

    const sisa = hitungSisaHari(langganan.tanggalBerakhir, sekarang);

    if (sisa < 0) {
        return 'KEDALUWARSA';
    }

    if (sisa <= AMBANG_AKAN_BERAKHIR) {
        return 'AKAN_BERAKHIR';
    }

    if (langganan.sumber === 'TRIAL') {
        return 'TRIAL';
    }

    return 'AKTIF';
}

/**
 * Langganan mana yang berlaku untuk seorang user: yang tanggal berakhirnya
 * paling jauh ke depan. Riwayat perpanjangan lama tetap tersimpan.
 */
export function langgananBerlaku(
    daftar: Langganan[],
    userId: string,
): Langganan | null {
    let terpilih: Langganan | null = null;

    for (const l of daftar) {
        if (l.userId !== userId) {
            continue;
        }

        if (
            !terpilih ||
            parseISO(l.tanggalBerakhir) > parseISO(terpilih.tanggalBerakhir)
        ) {
            terpilih = l;
        }
    }

    return terpilih;
}

/**
 * Tanggal berakhir setelah perpanjangan (PRD §F4.4).
 *
 * Kalau langganan MASIH berlaku → tambahkan dari tanggal berakhirnya
 * (user tidak kehilangan sisa hari yang sudah dibayar).
 * Kalau SUDAH kedaluwarsa → tambahkan dari hari ini.
 */
export function tanggalBerakhirBaru(
    langgananSaatIni: Langganan | null,
    durasi: DurasiPaket,
    sekarang: Date = new Date(),
): Date {
    const bulan = BULAN_PER_DURASI[durasi];
    const akhirSekarang = langgananSaatIni
        ? parseISO(langgananSaatIni.tanggalBerakhir)
        : null;

    const titikMulai =
        akhirSekarang && akhirSekarang > sekarang ? akhirSekarang : sekarang;

    return addMonths(titikMulai, bulan);
}

/** Apakah user boleh mengunduh ebook? (PRD §4.3) */
export function bolehUnduhEbook(status: StatusLangganan): boolean {
    return (
        status === 'AKTIF' || status === 'AKAN_BERAKHIR' || status === 'TRIAL'
    );
}

/** Gabungkan user + langganannya jadi bentuk yang dipakai tabel. */
export function ringkasUser(
    user: PosUser,
    semuaLangganan: Langganan[],
    sekarang: Date = new Date(),
) {
    const aktif = langgananBerlaku(semuaLangganan, user.id);

    return {
        ...user,
        langgananAktif: aktif,
        status: hitungStatusLangganan(aktif, user.ditangguhkan, sekarang),
        sisaHari: aktif ? hitungSisaHari(aktif.tanggalBerakhir, sekarang) : 0,
        durasi: aktif ? aktif.durasi : null,
    };
}
