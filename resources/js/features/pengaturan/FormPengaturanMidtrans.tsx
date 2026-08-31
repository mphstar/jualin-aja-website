import {
    EyeIcon,
    EyeOffIcon,
    InfoIcon,
    Loader2Icon,
    ShieldCheckIcon,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { KesalahanApi } from '@/lib/api';
import type { PengaturanMidtrans } from '@/lib/api';
import { usePengaturanStore } from '@/stores/pengaturanStore';

/**
 * Konfigurasi pembayaran Midtrans dari panel admin.
 *
 * Nilai kosong pada kunci mengartikan pembayaran otomatis mati (server menolak
 * pembuatan tagihan dan mengarahkan pengguna ke perpanjangan manual), jadi
 * simpannya tidak dilarang — hanya dinotice dalam form.
 */
export function FormPengaturanMidtrans() {
    const midtrans = usePengaturanStore((s) => s.midtrans);
    const memuatMidtrans = usePengaturanStore((s) => s.memuatMidtrans);
    const menyimpanMidtrans = usePengaturanStore((s) => s.menyimpanMidtrans);
    const sudahDimuatMidtrans = usePengaturanStore(
        (s) => s.sudahDimuatMidtrans,
    );
    const muatMidtrans = usePengaturanStore((s) => s.muatMidtrans);
    const simpanMidtrans = usePengaturanStore((s) => s.simpanMidtrans);

    // Config hanya dimuat saat halaman ini dibuka, bukan di seluruh panel.
    useEffect(() => {
        if (!sudahDimuatMidtrans && !memuatMidtrans) {
            void muatMidtrans();
        }
    }, [sudahDimuatMidtrans, memuatMidtrans, muatMidtrans]);

    const [nilai, setNilai] = useState<PengaturanMidtrans>(midtrans);
    const [nilaiTerakhir, setNilaiTerakhir] = useState(midtrans);
    const [tampilkanKunci, setTampilkanKunci] = useState(false);

    // Sinkronkan isian saat nilai tiba dari server atau berubah setelah disimpan.
    if (midtrans !== nilaiTerakhir) {
        setNilaiTerakhir(midtrans);
        setNilai(midtrans);
    }

    const berubah =
        nilai.server_key !== midtrans.server_key ||
        nilai.client_key !== midtrans.client_key ||
        nilai.is_production !== midtrans.is_production ||
        nilai.timeout !== midtrans.timeout;

    const kunciKosong = nilai.server_key.trim() === '';

    async function simpan() {
        try {
            await simpanMidtrans({
                server_key: nilai.server_key.trim(),
                client_key: nilai.client_key.trim(),
                is_production: nilai.is_production,
                timeout: Number(nilai.timeout) || 15,
            });
            toast.success('Pengaturan pembayaran disimpan');
        } catch (e) {
            toast.error(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Gagal menyimpan pengaturan pembayaran.',
            );
        }
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Pembayaran Midtrans</CardTitle>
                <p className="text-sm text-muted-foreground">
                    Kredensial dan mode (sandbox / produksi) gerbang pembayaran.
                </p>
            </CardHeader>

            <CardContent className="grid gap-4">
                <div className="flex items-start gap-3 rounded-md border bg-muted/50 p-3 text-sm text-muted-foreground">
                    <InfoIcon className="mt-0.5 size-4 shrink-0" />
                    <p>
                        {kunciKosong
                            ? 'Server key kosong: pembayaran otomatis mati dan tagihan hanya bisa diperpanjang secara manual.'
                            : 'Mode aktif: ' +
                              (nilai.is_production ? 'produksi' : 'sandbox') +
                              '. Perubahan berlaku untuk tagihan baru.'}
                    </p>
                </div>

                <div className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="midtrans-server-key">Server key</Label>
                        <div className="relative">
                            <Input
                                id="midtrans-server-key"
                                type={tampilkanKunci ? 'text' : 'password'}
                                autoComplete="off"
                                spellCheck={false}
                                value={nilai.server_key}
                                onChange={(e) =>
                                    setNilai((n) => ({
                                        ...n,
                                        server_key: e.target.value,
                                    }))
                                }
                            />
                            <button
                                type="button"
                                onClick={() => setTampilkanKunci((v) => !v)}
                                aria-label={
                                    tampilkanKunci
                                        ? 'Sembunyikan kunci'
                                        : 'Tampilkan kunci'
                                }
                                className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            >
                                {tampilkanKunci ? (
                                    <EyeOffIcon className="size-4" />
                                ) : (
                                    <EyeIcon className="size-4" />
                                )}
                            </button>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Dimulai dengan SB-Mid-server- untuk sandbox.
                        </p>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="midtrans-client-key">Client key</Label>
                        <Input
                            id="midtrans-client-key"
                            type={tampilkanKunci ? 'text' : 'password'}
                            autoComplete="off"
                            spellCheck={false}
                            value={nilai.client_key}
                            onChange={(e) =>
                                setNilai((n) => ({
                                    ...n,
                                    client_key: e.target.value,
                                }))
                            }
                        />
                    </div>

                    <div className="flex items-center justify-between gap-4 rounded-md border p-3">
                        <div className="flex items-start gap-3">
                            <ShieldCheckIcon className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                            <div>
                                <Label className="font-medium">
                                    Mode produksi
                                </Label>
                                <p className="text-sm text-muted-foreground">
                                    Mati = sandbox (percobaan, tanpa uang
                                    nyata).
                                </p>
                            </div>
                        </div>
                        <Switch
                            id="midtrans-produksi"
                            checked={nilai.is_production}
                            onCheckedChange={(v) =>
                                setNilai((n) => ({ ...n, is_production: v }))
                            }
                        />
                    </div>

                    <div className="grid gap-2 sm:max-w-[200px]">
                        <Label htmlFor="midtrans-timeout">
                            Batas waktu (detik)
                        </Label>
                        <Input
                            id="midtrans-timeout"
                            type="number"
                            min={1}
                            max={120}
                            className="angka-tabular"
                            value={String(nilai.timeout)}
                            onChange={(e) =>
                                setNilai((n) => ({
                                    ...n,
                                    timeout: Number(e.target.value),
                                }))
                            }
                        />
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <Button
                        onClick={simpan}
                        disabled={menyimpanMidtrans || !berubah}
                    >
                        {menyimpanMidtrans && (
                            <Loader2Icon className="size-4 animate-spin" />
                        )}
                        Simpan pembayaran
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
