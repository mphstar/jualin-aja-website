import { InfoIcon, Loader2Icon, RotateCcwIcon } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { KesalahanApi } from '@/lib/api';
import type { HargaPaket } from '@/lib/api';
import { formatRupiah } from '@/lib/format';
import { HARGA_PAKET_DEFAULT, LABEL_DURASI } from '@/lib/konstanta';
import { usePengaturanStore } from '@/stores/pengaturanStore';
import type { DurasiPaket } from '@/types';

const DAPAT_DIUBAH: DurasiPaket[] = ['BULANAN', 'SEMESTERAN', 'TAHUNAN'];

/** Harga disimpan sebagai teks supaya input kosong sementara tidak jadi 0. */
function keTeks(harga: HargaPaket): Record<string, string> {
    return Object.fromEntries(DAPAT_DIUBAH.map((d) => [d, String(harga[d])]));
}

export function FormHargaPaket() {
    const hargaPaket = usePengaturanStore((s) => s.hargaPaket);
    const menyimpan = usePengaturanStore((s) => s.menyimpan);
    const simpanHarga = usePengaturanStore((s) => s.simpanHarga);

    const [nilai, setNilai] = useState<Record<string, string>>(() =>
        keTeks(hargaPaket),
    );

    /*
     * Harga datang dari server (AppShell memuatnya sekali per sesi), jadi
     * isian form harus ikut saat nilainya tiba atau berubah setelah disimpan.
     * Disetel saat render, bukan di efek — kalau tidak, form sempat menampilkan
     * angka lama selama satu frame setelah penyimpanan berhasil.
     */
    const [hargaTerakhir, setHargaTerakhir] = useState(hargaPaket);

    if (hargaPaket !== hargaTerakhir) {
        setHargaTerakhir(hargaPaket);
        setNilai(keTeks(hargaPaket));
    }

    const adaKosong = DAPAT_DIUBAH.some(
        (d) => nilai[d] === '' || Number(nilai[d]) <= 0,
    );
    const berubah = DAPAT_DIUBAH.some(
        (d) => Number(nilai[d]) !== hargaPaket[d],
    );
    const samaDenganAwal = DAPAT_DIUBAH.every(
        (d) => hargaPaket[d] === HARGA_PAKET_DEFAULT[d],
    );

    async function simpan() {
        const baru: HargaPaket = {
            TRIAL: 0,
            BULANAN: Number(nilai.BULANAN),
            SEMESTERAN: Number(nilai.SEMESTERAN),
            TAHUNAN: Number(nilai.TAHUNAN),
        };

        try {
            await simpanHarga(baru);
            toast.success('Harga paket disimpan', {
                description: 'Berlaku untuk perpanjangan berikutnya.',
            });
        } catch (e) {
            toast.error(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Gagal menyimpan harga.',
            );
        }
    }

    async function kembalikan() {
        try {
            await simpanHarga(HARGA_PAKET_DEFAULT);
            toast.success('Harga dikembalikan ke nilai awal');
        } catch {
            toast.error('Gagal mengembalikan harga.');
        }
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Harga paket</CardTitle>
                <p className="text-sm text-muted-foreground">
                    Dipakai di dialog perpanjangan langganan.
                </p>
            </CardHeader>

            <CardContent className="grid gap-4">
                <div className="flex items-start gap-3 rounded-md border bg-muted/50 p-3 text-sm text-muted-foreground">
                    <InfoIcon className="mt-0.5 size-4 shrink-0" />
                    <p>
                        Invoice yang sudah terbit tidak ikut berubah —
                        nominalnya tercatat per transaksi. Perubahan hanya
                        berlaku untuk perpanjangan berikutnya.
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    {DAPAT_DIUBAH.map((d) => (
                        <div key={d} className="grid gap-2">
                            <Label htmlFor={`harga-${d}`}>
                                {LABEL_DURASI[d]}
                            </Label>
                            <div className="relative">
                                <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-muted-foreground">
                                    Rp
                                </span>
                                <Input
                                    id={`harga-${d}`}
                                    type="number"
                                    min={1}
                                    step={1000}
                                    className="angka-tabular pl-9"
                                    value={nilai[d] ?? ''}
                                    onChange={(e) =>
                                        setNilai((n) => ({
                                            ...n,
                                            [d]: e.target.value,
                                        }))
                                    }
                                />
                            </div>
                            <p className="angka-tabular text-xs text-muted-foreground">
                                {Number(nilai[d]) > 0
                                    ? formatRupiah(Number(nilai[d]))
                                    : 'Harus lebih dari 0'}
                            </p>
                        </div>
                    ))}
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <Button
                        onClick={simpan}
                        disabled={menyimpan || adaKosong || !berubah}
                    >
                        {menyimpan && (
                            <Loader2Icon className="size-4 animate-spin" />
                        )}
                        Simpan harga
                    </Button>
                    {!samaDenganAwal && (
                        <Button
                            variant="outline"
                            onClick={kembalikan}
                            disabled={menyimpan}
                        >
                            <RotateCcwIcon className="size-4" />
                            Kembalikan ke nilai awal
                        </Button>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
