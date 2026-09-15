/**
 * Mockup aplikasi kasir di dalam bingkai ponsel, untuk panel merek halaman
 * masuk.
 *
 * Seluruhnya hiasan: tidak ada data sungguhan dan tidak ada satu pun angka di
 * sini yang berarti, jadi semuanya ditandai `aria-hidden` dan angkanya ditulis
 * sebagai literal — bukan dibaca dari store mana pun. Kalau nanti angka di
 * layar ini harus ikut berubah, itu tanda ia sudah salah tempat.
 *
 * Layarnya sengaja selalu terang di kedua tema. Foto produk yang ikut menjadi
 * gelap saat pengguna memilih tema gelap akan terbaca sebagai gambar rusak,
 * bukan sebagai tema.
 */

/** Tinggi tiap batang grafik, dalam persen. Polanya sengaja tidak rapi berurut. */
const BATANG = [44, 72, 38, 92, 58, 78, 50];

const BARIS = [
    { label: 'Minuman', nilai: '780.000' },
    { label: 'Makanan', nilai: '420.000' },
];

export function MockupKasir() {
    return (
        <div aria-hidden className="w-56 -rotate-6 select-none">
            {/* Bingkai ponsel */}
            <div className="rounded-[2.75rem] bg-zinc-950 p-2 shadow-2xl ring-1 shadow-black/60 ring-white/10">
                <div className="relative overflow-hidden rounded-[2.15rem] bg-white">
                    {/* Pulau dinamis */}
                    <div className="absolute top-2.5 left-1/2 h-4 w-14 -translate-x-1/2 rounded-full bg-zinc-950" />

                    <div className="px-4 pt-10 pb-4">
                        <div className="flex items-center justify-between">
                            <span className="text-[0.6rem] font-medium text-zinc-400">
                                Pekan ini
                            </span>
                            <span className="size-4 rounded-full border border-zinc-200" />
                        </div>

                        <p className="mt-1.5 flex items-baseline gap-1">
                            <span className="text-[0.65rem] font-medium text-zinc-400">
                                Rp
                            </span>
                            <span className="angka-tabular text-[1.6rem] leading-none font-medium tracking-tight text-zinc-900">
                                4.820.000
                            </span>
                        </p>

                        <div className="mt-5 flex h-20 items-end gap-1.5">
                            {BATANG.map((tinggi, urutan) => (
                                <div
                                    key={urutan}
                                    className="flex h-full flex-1 items-end rounded-full bg-zinc-100"
                                >
                                    <div
                                        className="w-full rounded-full bg-oranye"
                                        style={{ height: `${tinggi}%` }}
                                    />
                                </div>
                            ))}
                        </div>

                        <div className="mt-4 grid gap-2.5 border-t border-zinc-100 pt-3.5">
                            {BARIS.map((baris) => (
                                <div
                                    key={baris.label}
                                    className="flex items-center gap-2"
                                >
                                    <span className="size-1.5 shrink-0 rounded-full bg-oranye" />
                                    <span className="flex-1 text-[0.65rem] text-zinc-500">
                                        {baris.label}
                                    </span>
                                    <span className="angka-tabular text-[0.65rem] font-medium text-zinc-900">
                                        {baris.nilai}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Bilah tab bawah: tiga menu pasif, satu sedang aktif. */}
                    <div className="flex items-center justify-around border-t border-zinc-100 px-5 py-3">
                        {[0, 1, 2, 3].map((urutan) => (
                            <span
                                key={urutan}
                                className={
                                    urutan === 0
                                        ? 'h-1.5 w-6 rounded-full bg-oranye'
                                        : 'size-1.5 rounded-full bg-zinc-200'
                                }
                            />
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
