import {
    CheckIcon,
    CopyIcon,
    EyeIcon,
    EyeOffIcon,
    InfoIcon,
    Loader2Icon,
    ShieldCheckIcon,
    WebhookIcon,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { KesalahanApi } from '@/lib/api';
import type { PengaturanMayar } from '@/lib/api';
import { usePengaturanStore } from '@/stores/pengaturanStore';

/**
 * Konfigurasi pembayaran Mayar dari panel admin.
 *
 * Nilai kosong pada kunci mengartikan pembayaran otomatis mati (server menolak
 * pembuatan tagihan dan mengarahkan pengguna ke perpanjangan manual), jadi
 * simpannya tidak dilarang — hanya dinotice dalam form.
 */
export function FormPengaturanMayar() {
    const mayar = usePengaturanStore((s) => s.mayar);
    const memuatMayar = usePengaturanStore((s) => s.memuatMayar);
    const menyimpanMayar = usePengaturanStore((s) => s.menyimpanMayar);
    const sudahDimuatMayar = usePengaturanStore((s) => s.sudahDimuatMayar);
    const muatMayar = usePengaturanStore((s) => s.muatMayar);
    const simpanMayar = usePengaturanStore((s) => s.simpanMayar);

    // Config hanya dimuat saat halaman ini dibuka, bukan di seluruh panel.
    useEffect(() => {
        if (!sudahDimuatMayar && !memuatMayar) {
            void muatMayar();
        }
    }, [sudahDimuatMayar, memuatMayar, muatMayar]);

    const [nilai, setNilai] = useState<PengaturanMayar>(mayar);
    const [nilaiTerakhir, setNilaiTerakhir] = useState(mayar);
    const [tampilkanKunci, setTampilkanKunci] = useState(false);
    const [tampilkanSecret, setTampilkanSecret] = useState(false);
    const [sudahSalin, setSudahSalin] = useState(false);

    // Sinkronkan isian saat nilai tiba dari server atau berubah setelah disimpan.
    if (mayar !== nilaiTerakhir) {
        setNilaiTerakhir(mayar);
        setNilai(mayar);
    }

    const secretBersih = (nilai.webhook_secret ?? '').trim();
    const secretBawaanBersih = (mayar.webhook_secret ?? '').trim();

    const berubah =
        nilai.api_key !== mayar.api_key ||
        secretBersih !== secretBawaanBersih ||
        nilai.is_production !== mayar.is_production ||
        nilai.timeout !== mayar.timeout;

    const kunciKosong = nilai.api_key.trim() === '';

    const webhookUrl =
        typeof window !== 'undefined'
            ? `${window.location.origin}/api/mayar/notifikasi/${secretBersih || '<WEBHOOK_SECRET>'}`
            : `/api/mayar/notifikasi/${secretBersih || '<WEBHOOK_SECRET>'}`;

    async function salinWebhookUrl() {
        if (!secretBersih) {
            toast.error('Isi Webhook Secret terlebih dahulu.');
            return;
        }
        try {
            await navigator.clipboard.writeText(
                `${window.location.origin}/api/mayar/notifikasi/${secretBersih}`,
            );
            setSudahSalin(true);
            toast.success('URL Webhook berhasil disalin');
            setTimeout(() => setSudahSalin(false), 2000);
        } catch {
            toast.error('Gagal menyalin ke papan klip.');
        }
    }

    async function simpan() {
        try {
            await simpanMayar({
                api_key: nilai.api_key.trim(),
                webhook_secret: secretBersih,
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
                <CardTitle className="text-base">Pembayaran Mayar</CardTitle>
                <p className="text-sm text-muted-foreground">
                    Kredensial, webhook secret, dan mode (sandbox / produksi) gerbang pembayaran.
                </p>
            </CardHeader>

            <CardContent className="grid gap-4">
                <div className="flex items-start gap-3 rounded-md border bg-muted/50 p-3 text-sm text-muted-foreground">
                    <InfoIcon className="mt-0.5 size-4 shrink-0" />
                    <p>
                        {kunciKosong
                            ? 'API key kosong: pembayaran otomatis mati dan tagihan hanya bisa diperpanjang secara manual.'
                            : 'Mode aktif: ' +
                              (nilai.is_production ? 'produksi' : 'sandbox') +
                              '. Perubahan berlaku untuk tagihan baru.'}
                    </p>
                </div>

                <div className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="mayar-api-key">API key</Label>
                        <div className="relative">
                            <Input
                                id="mayar-api-key"
                                type={tampilkanKunci ? 'text' : 'password'}
                                autoComplete="off"
                                spellCheck={false}
                                value={nilai.api_key}
                                onChange={(e) =>
                                    setNilai((n) => ({
                                        ...n,
                                        api_key: e.target.value,
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
                            Diambil dari panel Mayar, menu Integration / API.
                        </p>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="mayar-webhook-secret">Webhook Secret</Label>
                        <div className="relative">
                            <Input
                                id="mayar-webhook-secret"
                                type={tampilkanSecret ? 'text' : 'password'}
                                autoComplete="off"
                                spellCheck={false}
                                placeholder="Contoh: rahasia_webhook_mayar_anda_123"
                                value={nilai.webhook_secret ?? ''}
                                onChange={(e) =>
                                    setNilai((n) => ({
                                        ...n,
                                        webhook_secret: e.target.value,
                                    }))
                                }
                            />
                            <button
                                type="button"
                                onClick={() => setTampilkanSecret((v) => !v)}
                                aria-label={
                                    tampilkanSecret
                                        ? 'Sembunyikan secret'
                                        : 'Tampilkan secret'
                                }
                                className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            >
                                {tampilkanSecret ? (
                                    <EyeOffIcon className="size-4" />
                                ) : (
                                    <EyeIcon className="size-4" />
                                )}
                            </button>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Kunci rahasia untuk memvalidasi webhook notifikasi dari Mayar.
                        </p>
                    </div>

                    <div className="rounded-md border bg-muted/30 p-3">
                        <div className="flex items-center justify-between gap-2 mb-1.5">
                            <div className="flex items-center gap-2 text-sm font-medium">
                                <WebhookIcon className="size-4 text-primary" />
                                <span>URL Webhook Mayar</span>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="h-7 gap-1.5 text-xs"
                                onClick={salinWebhookUrl}
                            >
                                {sudahSalin ? (
                                    <CheckIcon className="size-3.5 text-green-600" />
                                ) : (
                                    <CopyIcon className="size-3.5" />
                                )}
                                {sudahSalin ? 'Tersalin' : 'Salin URL'}
                            </Button>
                        </div>
                        <code className="block rounded bg-background p-2 text-xs font-mono break-all text-muted-foreground select-all">
                            {webhookUrl}
                        </code>
                        <p className="mt-1.5 text-xs text-muted-foreground">
                            Pasang URL ini pada menu <strong>Integration → Webhook</strong> di dashboard Mayar agar status pembayaran tersinkron otomatis ke database saat pembayaran lunas.
                        </p>
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
                            id="mayar-produksi"
                            checked={nilai.is_production}
                            onCheckedChange={(v) =>
                                setNilai((n) => ({ ...n, is_production: v }))
                            }
                        />
                    </div>

                    <div className="grid gap-2 sm:max-w-[200px]">
                        <Label htmlFor="mayar-timeout">
                            Batas waktu (detik)
                        </Label>
                        <Input
                            id="mayar-timeout"
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
                        disabled={menyimpanMayar || !berubah}
                    >
                        {menyimpanMayar && (
                            <Loader2Icon className="size-4 animate-spin" />
                        )}
                        Simpan pembayaran
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}