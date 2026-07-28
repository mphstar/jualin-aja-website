import { create } from 'zustand';
import { api, KesalahanApi } from '@/lib/api';
import type {
    IrisanPaket,
    LogAktivitas,
    PosUserRingkas,
    StatistikDasbor,
    TitikDeret,
} from '@/types';

const STATISTIK_KOSONG: StatistikDasbor = {
    totalUser: 0,
    deltaUserPersen: 0,
    langgananAktif: 0,
    deltaAktifPersen: 0,
    akanBerakhir: 0,
    kedaluwarsa: 0,
    pendapatanBulanIni: 0,
    deltaPendapatanPersen: 0,
    tokoMemakaiKasir: 0,
};

interface DasborState {
    statistik: StatistikDasbor;
    deretPendaftaran: TitikDeret[];
    deretPendapatan: TitikDeret[];
    komposisiPaket: IrisanPaket[];
    akanBerakhir: PosUserRingkas[];
    aktivitasTerbaru: LogAktivitas[];
    memuat: boolean;
    error: string | null;

    ambilSemua: () => Promise<void>;
}

export const useDasborStore = create<DasborState>()((set) => ({
    statistik: STATISTIK_KOSONG,
    deretPendaftaran: [],
    deretPendapatan: [],
    komposisiPaket: [],
    akanBerakhir: [],
    aktivitasTerbaru: [],
    memuat: false,
    error: null,

    ambilSemua: async () => {
        set({ memuat: true, error: null });

        try {
            // Semua permintaan berjalan paralel: dasbor menampilkan satu potret
            // waktu, jadi tidak ada gunanya menunggunya berurutan.
            const [
                statistik,
                deretPendaftaran,
                deretPendapatan,
                komposisiPaket,
                akanBerakhir,
                aktivitasTerbaru,
            ] = await Promise.all([
                api.statistik.ambilStatistikDasbor(),
                api.statistik.ambilTrenPendaftaran(),
                api.statistik.ambilTrenPendapatan(),
                api.statistik.ambilKomposisiPaket(),
                api.pengguna.ambilAkanBerakhir(10),
                api.aktivitas.ambilAktivitasTerbaru(8),
            ]);

            set({
                statistik,
                deretPendaftaran,
                deretPendapatan,
                komposisiPaket,
                akanBerakhir,
                aktivitasTerbaru,
                memuat: false,
            });
        } catch (e) {
            set({
                memuat: false,
                error:
                    e instanceof KesalahanApi
                        ? e.message
                        : 'Tidak dapat memuat data dasbor.',
            });
        }
    },
}));
