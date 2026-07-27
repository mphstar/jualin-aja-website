import { Loader2Icon, TriangleAlertIcon } from 'lucide-react';
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
import { Textarea } from '@/components/ui/textarea';
import { KesalahanApi } from '@/lib/api';

const MIN_ALASAN = 10;

interface Props {
    /** Toko yang akan ditangguhkan; `null` menutup dialog. */
    target: { id: string; namaToko: string } | null;
    onTutup: () => void;
    onKirim: (id: string, alasan: string) => Promise<void>;
}

/**
 * Penangguhan wajib beralasan (PRD §F3.7) — alasannya ikut tercatat
 * di log aktivitas, jadi tidak boleh kosong.
 */
export function DialogTangguhkan({ target, onTutup, onKirim }: Props) {
    const [alasan, setAlasan] = useState('');
    const [tersentuh, setTersentuh] = useState(false);
    const [mengirim, setMengirim] = useState(false);

    // Bersihkan isian setiap dialog dibuka untuk target baru; disetel saat
    // render supaya alasan milik toko sebelumnya tidak sempat terlihat.
    const [targetSebelumnya, setTargetSebelumnya] = useState(target);

    if (target !== targetSebelumnya) {
        setTargetSebelumnya(target);

        if (target) {
            setAlasan('');
            setTersentuh(false);
        }
    }

    if (!target) {
        return null;
    }

    const kurang = alasan.trim().length < MIN_ALASAN;
    const tampilkanError = tersentuh && kurang;

    async function kirim() {
        if (!target) {
            return;
        }

        setTersentuh(true);

        if (kurang) {
            return;
        }

        setMengirim(true);

        try {
            await onKirim(target.id, alasan.trim());
            toast.success('Akun ditangguhkan', {
                description: `${target.namaToko} tidak bisa lagi mengakses aplikasi POS.`,
            });
            onTutup();
        } catch (e) {
            toast.error(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Gagal menangguhkan akun.',
            );
        } finally {
            setMengirim(false);
        }
    }

    return (
        <Dialog open onOpenChange={(o) => !o && !mengirim && onTutup()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Tangguhkan akun</DialogTitle>
                    <DialogDescription>
                        <span className="font-medium text-foreground">
                            {target.namaToko}
                        </span>{' '}
                        akan kehilangan akses aplikasi POS sampai dipulihkan
                        kembali.
                    </DialogDescription>
                </DialogHeader>

                <div className="flex items-start gap-3 rounded-md border border-peringatan/25 bg-peringatan-lembut p-3 text-sm text-peringatan">
                    <TriangleAlertIcon className="mt-0.5 size-4 shrink-0" />
                    <p>
                        Masa langganan tetap berjalan selama akun ditangguhkan —
                        waktunya tidak dihentikan.
                    </p>
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="alasan-tangguh">
                        Alasan penangguhan{' '}
                        <span className="text-destructive">*</span>
                    </Label>
                    <Textarea
                        id="alasan-tangguh"
                        value={alasan}
                        onChange={(e) => setAlasan(e.target.value)}
                        onBlur={() => setTersentuh(true)}
                        placeholder="Mis. terindikasi menyalahgunakan akun untuk beberapa toko sekaligus."
                        rows={3}
                        maxLength={500}
                        aria-invalid={tampilkanError}
                    />
                    {tampilkanError ? (
                        <p className="text-sm text-destructive">
                            Alasan wajib diisi, minimal {MIN_ALASAN} karakter.
                        </p>
                    ) : (
                        <p className="text-xs text-muted-foreground">
                            Alasan ini tersimpan di log aktivitas.
                        </p>
                    )}
                </div>

                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={onTutup}
                        disabled={mengirim}
                    >
                        Batal
                    </Button>
                    <Button
                        variant="destructive"
                        onClick={kirim}
                        disabled={mengirim}
                    >
                        {mengirim && (
                            <Loader2Icon className="size-4 animate-spin" />
                        )}
                        {mengirim ? 'Memproses…' : 'Tangguhkan'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
