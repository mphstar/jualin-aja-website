import { CalendarCheckIcon, Loader2Icon } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import { api, KesalahanApi } from '@/lib/api';
import { formatRupiah, formatTanggalPanjang } from '@/lib/format';
import { LABEL_DURASI } from '@/lib/konstanta';
import { tanggalBerakhirBaru } from '@/lib/langganan';
import { usePengaturanStore } from '@/stores/pengaturanStore';
import type { DurasiPaket, Langganan } from '@/types';

const PILIHAN_DURASI: DurasiPaket[] = ['BULANAN', 'SEMESTERAN', 'TAHUNAN'];

export interface TargetPerpanjang {
    userId: string;
    namaToko: string;
    langgananAktif: Langganan | null;
}

interface Props {
    target: TargetPerpanjang | null;
    onTutup: () => void;
    onBerhasil?: () => void;
}

export function DialogPerpanjang({ target, onTutup, onBerhasil }: Props) {
    const hargaPaket = usePengaturanStore((s) => s.hargaPaket);
    const [durasi, setDurasi] = useState<DurasiPaket>('BULANAN');
    const [catatan, setCatatan] = useState('');
    const [mengirim, setMengirim] = useState(false);

    /*
     * Kembalikan ke kondisi awal setiap dialog dibuka untuk target baru.
     * Disetel saat render, bukan di dalam efek: React langsung mengulang render
     * sebelum menggambar, jadi isian dialog tidak pernah sempat terlihat masih
     * memuat pilihan target sebelumnya.
     */
    const [targetSebelumnya, setTargetSebelumnya] = useState(target);

    if (target !== targetSebelumnya) {
        setTargetSebelumnya(target);

        if (target) {
            setDurasi('BULANAN');
            setCatatan('');
        }
    }

    if (!target) {
        return null;
    }

    const sekarang = new Date();
    const akhirLama = target.langgananAktif?.tanggalBerakhir;
    const masihBerlaku = akhirLama ? new Date(akhirLama) > sekarang : false;
    const akhirBaru = tanggalBerakhirBaru(
        target.langgananAktif,
        durasi,
        sekarang,
    );

    async function kirim() {
        if (!target) {
            return;
        }

        setMengirim(true);

        try {
            await api.langganan.perpanjangLangganan({
                userId: target.userId,
                durasi,
                catatan: catatan.trim() || undefined,
            });
            toast.success('Langganan diperpanjang', {
                description: `${target.namaToko} kini aktif hingga ${formatTanggalPanjang(akhirBaru.toISOString())}.`,
            });
            onBerhasil?.();
            onTutup();
        } catch (e) {
            toast.error(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Gagal memperpanjang langganan.',
            );
        } finally {
            setMengirim(false);
        }
    }

    return (
        <Dialog open onOpenChange={(o) => !o && onTutup()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Perpanjang langganan</DialogTitle>
                    <DialogDescription>
                        Menambah masa aktif untuk{' '}
                        <span className="font-medium text-foreground">
                            {target.namaToko}
                        </span>
                        .
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-4">
                    <div className="grid gap-2">
                        <Label>Durasi tambahan</Label>
                        <RadioGroup
                            value={durasi}
                            onValueChange={(v) => setDurasi(v as DurasiPaket)}
                            className="gap-2"
                        >
                            {PILIHAN_DURASI.map((d) => (
                                <Label
                                    key={d}
                                    htmlFor={`durasi-${d}`}
                                    className="flex cursor-pointer items-center gap-3 rounded-md border p-3 font-normal transition-colors hover:bg-accent has-[[data-state=checked]]:border-primary"
                                >
                                    <RadioGroupItem
                                        value={d}
                                        id={`durasi-${d}`}
                                    />
                                    <span className="flex-1 font-medium">
                                        {LABEL_DURASI[d]}
                                    </span>
                                    <span className="angka-tabular text-sm text-muted-foreground">
                                        {formatRupiah(hargaPaket[d])}
                                    </span>
                                </Label>
                            ))}
                        </RadioGroup>
                    </div>

                    <div className="flex items-start gap-3 rounded-md border bg-muted/50 p-3">
                        <CalendarCheckIcon className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                        <div className="text-sm">
                            <p className="font-medium">
                                Berakhir{' '}
                                {formatTanggalPanjang(akhirBaru.toISOString())}
                            </p>
                            <p className="mt-0.5 text-xs text-muted-foreground">
                                {masihBerlaku
                                    ? `Dihitung dari tanggal berakhir saat ini (${formatTanggalPanjang(akhirLama!)}), jadi sisa hari yang sudah dibayar tidak hangus.`
                                    : 'Langganan sudah kedaluwarsa, jadi masa aktif dihitung dari hari ini.'}
                            </p>
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="catatan-perpanjang">
                            Catatan (opsional)
                        </Label>
                        <Textarea
                            id="catatan-perpanjang"
                            value={catatan}
                            onChange={(e) => setCatatan(e.target.value)}
                            placeholder="Mis. pembayaran via transfer BCA, sudah dikonfirmasi."
                            rows={2}
                            maxLength={500}
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={onTutup}
                        disabled={mengirim}
                    >
                        Batal
                    </Button>
                    <Button onClick={kirim} disabled={mengirim}>
                        {mengirim && (
                            <Loader2Icon className="size-4 animate-spin" />
                        )}
                        {mengirim ? 'Memproses…' : 'Perpanjang'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
