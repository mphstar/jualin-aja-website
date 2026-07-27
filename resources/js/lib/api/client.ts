import { router } from '@inertiajs/react';

/**
 * Klien HTTP untuk `/api/v1`.
 *
 * Autentikasinya cookie sesi — sama dengan yang dipakai halaman Inertia —
 * jadi tidak ada token yang perlu disimpan, dilampirkan, atau disegarkan di
 * sisi klien. Yang wajib dibawa hanya token CSRF untuk permintaan yang menulis.
 */
const BASIS = '/api/v1';

/** Kesalahan API. `field` terisi untuk 422, dipetakan langsung ke input form. */
export class KesalahanApi extends Error {
    status: number;
    field?: Record<string, string>;

    constructor(status: number, pesan: string, field?: Record<string, string>) {
        super(pesan);
        this.name = 'KesalahanApi';
        this.status = status;
        this.field = field;
    }
}

/**
 * Laravel menaruh token CSRF di cookie `XSRF-TOKEN` dan mengharapkannya
 * kembali sebagai header. Nilainya di-URL-encode, jadi harus didekode dulu —
 * kalau tidak, tanda `=` di ujung token membuat verifikasinya gagal.
 */
function tokenCsrf(): string {
    const cocok = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    return cocok?.[1] ? decodeURIComponent(cocok[1]) : '';
}

type NilaiParam = string | number | boolean | undefined | null;

function bangunUrl(jalur: string, params?: Record<string, NilaiParam>): string {
    if (!params) {
        return BASIS + jalur;
    }

    const kueri = new URLSearchParams();

    for (const [kunci, nilai] of Object.entries(params)) {
        if (nilai === undefined || nilai === null || nilai === '') {
            continue;
        }

        // Laravel membaca boolean dari "1"/"0"; `String(true)` tidak dikenali.
        kueri.set(
            kunci,
            typeof nilai === 'boolean' ? (nilai ? '1' : '0') : String(nilai),
        );
    }

    const teks = kueri.toString();

    return teks ? `${BASIS}${jalur}?${teks}` : BASIS + jalur;
}

/** Ubah amplop galat Laravel jadi KesalahanApi yang bisa dipakai form. */
async function keKesalahan(respons: Response): Promise<KesalahanApi> {
    let badan: unknown = null;

    try {
        badan = await respons.json();
    } catch {
        // Balasan tanpa JSON (mis. 500 dari proxy) — pakai pesan bawaan di bawah.
    }

    const data = (badan ?? {}) as {
        message?: string;
        errors?: Record<string, string[]>;
    };

    // Ambil pesan PERTAMA tiap field: form hanya punya ruang untuk satu baris.
    const field = data.errors
        ? Object.fromEntries(
              Object.entries(data.errors).map(([kunci, pesan]) => [
                  kunci,
                  pesan[0] ?? '',
              ]),
          )
        : undefined;

    const pesan =
        data.message ??
        (respons.status >= 500
            ? 'Terjadi kesalahan di server. Coba lagi.'
            : 'Permintaan gagal diproses.');

    return new KesalahanApi(respons.status, pesan, field);
}

interface OpsiMinta {
    params?: Record<string, NilaiParam>;
    badan?: unknown;
}

async function minta<T>(
    metode: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE',
    jalur: string,
    opsi: OpsiMinta = {},
): Promise<T> {
    const menulis = metode !== 'GET';
    const multipart = opsi.badan instanceof FormData;

    const respons = await fetch(bangunUrl(jalur, opsi.params), {
        method: metode,
        // Sesi ada di cookie; tanpa ini setiap permintaan datang sebagai tamu.
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            // Content-Type multipart TIDAK boleh disetel manual — peramban perlu
            // menambahkan boundary-nya sendiri.
            ...(menulis && !multipart
                ? { 'Content-Type': 'application/json' }
                : {}),
            ...(menulis ? { 'X-XSRF-TOKEN': tokenCsrf() } : {}),
        },
        body: multipart
            ? (opsi.badan as FormData)
            : opsi.badan !== undefined
              ? JSON.stringify(opsi.badan)
              : undefined,
    });

    if (respons.ok) {
        return respons.status === 204
            ? (undefined as T)
            : ((await respons.json()) as T);
    }

    if (respons.status === 401) {
        // Sesi habis di tengah pemakaian. Halaman dimuat ulang lewat server supaya
        // pengguna mendarat di /login dengan tujuan semula tersimpan.
        router.visit('/login');
    }

    if (respons.status === 419) {
        // Token CSRF kedaluwarsa — hanya muat ulang yang bisa mengambil yang baru.
        window.location.reload();
    }

    throw await keKesalahan(respons);
}

export const ambil = <T>(jalur: string, params?: Record<string, NilaiParam>) =>
    minta<T>('GET', jalur, { params });

export const kirim = <T>(jalur: string, badan?: unknown) =>
    minta<T>('POST', jalur, { badan });

export const tambal = <T>(jalur: string, badan?: unknown) =>
    minta<T>('PATCH', jalur, { badan });

export const ganti = <T>(jalur: string, badan?: unknown) =>
    minta<T>('PUT', jalur, { badan });

export const hapus = (jalur: string) => minta<void>('DELETE', jalur);

/** Bangun FormData dari objek; nilai kosong dilewati, boolean jadi "1"/"0". */
export function keFormData(nilai: Record<string, unknown>): FormData {
    const data = new FormData();

    for (const [kunci, isi] of Object.entries(nilai)) {
        if (isi === undefined || isi === null) {
            continue;
        }

        if (isi instanceof File) {
            data.append(kunci, isi);
        } else if (typeof isi === 'boolean') {
            data.append(kunci, isi ? '1' : '0');
        } else {
            data.append(kunci, String(isi));
        }
    }

    return data;
}
