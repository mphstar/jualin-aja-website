import { TombolTema } from '@/components/layout/TombolTema';
import { MockupKasir } from '@/features/auth/MockupKasir';

/**
 * Panel merek di sisi kiri halaman masuk: kalimat pembuka, judul besar, dan
 * mockup aplikasi kasir di atas latar pekat.
 *
 * Hanya tampil mulai lebar `lg`. Di layar sempit panel ini cuma akan mendorong
 * formulir ke bawah lipatan tanpa menambah keterangan apa pun — mereknya sudah
 * dibawa header kartu di sebelahnya.
 */
export function PanelMerek() {
    return (
        <aside className="relative hidden overflow-hidden bg-pekat lg:flex lg:flex-col lg:px-12 lg:pt-10 xl:px-16">
            <Cincin />

            {/* Sorotan hangat di belakang mockup. */}
            <div
                aria-hidden
                className="absolute -bottom-24 left-1/2 size-96 -translate-x-1/2 rounded-full bg-oranye/15 blur-3xl"
            />

            <p className="relative z-10 max-w-[22rem] text-xs leading-relaxed text-white/55">
                Kasir, katalog resep, dan laporan penjualan UMKM dalam satu
                langganan.
            </p>

            <h2 className="relative z-10 mt-[15vh] text-center text-5xl leading-[1.06] font-normal tracking-[-0.035em] text-white xl:text-6xl 2xl:text-7xl">
                Kelola
                <br />
                semua toko
            </h2>

            {/* Ukurannya diatur lewat `scale`, bukan lebar, karena jarak dan
                ukuran huruf di dalam mockup semuanya angka tetap — melebarkan
                wadahnya cuma akan memanjangkan batang grafiknya. Skala juga
                yang membuat mockup ini menjorok ke luar tepi bawah panel; sisa
                yang terpotong `overflow-hidden` di atas memang disengaja,
                sebab mockup yang utuh terbaca seperti gambar produk yang
                ditempel, bukan bagian dari komposisi. */}
            <div className="relative z-10 mt-auto flex justify-center">
                <div className="scale-110 xl:scale-[1.3] 2xl:scale-[1.45]">
                    <MockupKasir />
                </div>
            </div>

            {/* Slot bulat di pojok kanan bawah, sama seperti di referensi —
                isinya tombol tema, bukan hiasan mati. Tombol tema bawaan
                berwarna untuk kertas terang, jadi warnanya dipaksa di sini. */}
            <div className="absolute right-8 bottom-8 z-20 [&_button]:size-9 [&_button]:rounded-full [&_button]:bg-white/10 [&_button]:text-white/70 [&_button:hover]:bg-white/20 [&_button:hover]:text-white">
                <TombolTema />
            </div>
        </aside>
    );
}

/** Cincin konsentris di belakang judul — dekorasi tanpa makna. */
function Cincin() {
    return (
        <svg
            aria-hidden
            viewBox="0 0 800 800"
            fill="none"
            stroke="currentColor"
            className="pointer-events-none absolute top-[10%] left-1/2 size-[40rem] -translate-x-1/2 text-white/6"
        >
            {[150, 250, 340].map((jariJari) => (
                <circle
                    key={jariJari}
                    cx="400"
                    cy="400"
                    r={jariJari}
                    strokeWidth="1.25"
                />
            ))}
        </svg>
    );
}
