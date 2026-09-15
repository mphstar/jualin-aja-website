import { LABEL_DURASI, LABEL_TIPE_PEMBAYARAN } from '@/lib/konstanta';
import type { Pembayaran } from '@/types';

/** Bagian `Pembayaran` yang menentukan teksnya. Cukup untuk tabel & kartu. */
type Potongan = Pick<Pembayaran, 'tipe' | 'durasi' | 'ebookJudul'>;

/**
 * Isi kolom "Paket / Konten" pada riwayat pembayaran.
 *
 * Tagihan langganan memakai durasinya; pembelian Pustaka satuan tidak punya
 * durasi sama sekali (kolomnya boleh null), jadi yang ditampilkan adalah judul
 * kontennya. Sebelumnya kolom `durasi` dipaksa berisi `BULANAN`, dan baris itu
 * terbaca sebagai pembelian langganan satu bulan di mana pun yang tidak
 * memeriksa `tipe`.
 */
export function labelPaketPembayaran(pembayaran: Potongan): string {
    if (pembayaran.tipe === 'PUSTAKA_SATUAN') {
        return pembayaran.ebookJudul ?? LABEL_TIPE_PEMBAYARAN.PUSTAKA_SATUAN;
    }

    return pembayaran.durasi ? LABEL_DURASI[pembayaran.durasi] : '—';
}

/**
 * Apa yang sebenarnya terjadi saat invoice ditandai lunas.
 *
 * Dua jenis tagihan berakhir di tempat berbeda: langganan memperpanjang masa
 * aktif, pembelian satuan membuka akses konten dan tidak menyentuh langganan
 * sama sekali. Kalimatnya karena itu wajib ikut jenisnya — versi sebelumnya
 * menulis "langganan diperpanjang" untuk keduanya, padahal
 * `TandaiPembayaranLunas` hanya memperpanjang yang berjenis langganan.
 *
 * Ditulis sekali di sini karena dipakai dua layar, dan dua salinan kalimat
 * yang menjelaskan aturan justru cara paling mudah membuatnya berbeda.
 */
export function kalimatPelunasan(
    pembayaran: Potongan,
    namaToko: string,
): string {
    return pembayaran.tipe === 'PUSTAKA_SATUAN'
        ? `Akses "${labelPaketPembayaran(pembayaran)}" terbuka untuk ${namaToko}.`
        : `Langganan ${namaToko} otomatis diperpanjang ${labelPaketPembayaran(pembayaran)}.`;
}
