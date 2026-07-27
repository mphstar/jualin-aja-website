import { Loader2Icon } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface DialogKonfirmasiProps {
    buka: boolean;
    onTutup: () => void;
    judul: string;
    keterangan?: ReactNode;
    labelKonfirmasi?: string;
    labelBatal?: string;
    destruktif?: boolean;
    /**
     * Bila diisi, admin wajib mengetik ulang teks ini persis sebelum
     * tombol konfirmasi aktif — pengaman untuk aksi yang tidak bisa
     * dibatalkan (PRD §8, "aksi destruktif").
     */
    ketikUntukKonfirmasi?: string;
    onKonfirmasi: () => Promise<void> | void;
}

export function DialogKonfirmasi({
    buka,
    onTutup,
    judul,
    keterangan,
    labelKonfirmasi = 'Konfirmasi',
    labelBatal = 'Batal',
    destruktif,
    ketikUntukKonfirmasi,
    onKonfirmasi,
}: DialogKonfirmasiProps) {
    const [ketikan, setKetikan] = useState('');
    const [memproses, setMemproses] = useState(false);

    // Kosongkan konfirmasi ketik-ulang setiap dialog dibuka. Disetel saat
    // render supaya ketikan dari pembukaan sebelumnya tidak sempat terlihat.
    const [bukaSebelumnya, setBukaSebelumnya] = useState(buka);

    if (buka !== bukaSebelumnya) {
        setBukaSebelumnya(buka);

        if (buka) {
            setKetikan('');
        }
    }

    const cocok =
        !ketikUntukKonfirmasi ||
        ketikan.trim().toLowerCase() ===
            ketikUntukKonfirmasi.trim().toLowerCase();

    async function jalankan() {
        setMemproses(true);

        try {
            await onKonfirmasi();
            onTutup();
        } finally {
            setMemproses(false);
        }
    }

    return (
        <Dialog open={buka} onOpenChange={(o) => !o && !memproses && onTutup()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{judul}</DialogTitle>
                    {keterangan && (
                        <DialogDescription asChild>
                            <div>{keterangan}</div>
                        </DialogDescription>
                    )}
                </DialogHeader>

                {ketikUntukKonfirmasi && (
                    <div className="grid gap-2">
                        <Label htmlFor="ketik-konfirmasi">
                            Ketik{' '}
                            <span className="font-medium text-foreground">
                                {ketikUntukKonfirmasi}
                            </span>{' '}
                            untuk melanjutkan
                        </Label>
                        <Input
                            id="ketik-konfirmasi"
                            value={ketikan}
                            onChange={(e) => setKetikan(e.target.value)}
                            autoComplete="off"
                        />
                    </div>
                )}

                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={onTutup}
                        disabled={memproses}
                    >
                        {labelBatal}
                    </Button>
                    <Button
                        variant={destruktif ? 'destructive' : 'default'}
                        onClick={jalankan}
                        disabled={memproses || !cocok}
                    >
                        {memproses && (
                            <Loader2Icon className="size-4 animate-spin" />
                        )}
                        {memproses ? 'Memproses…' : labelKonfirmasi}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
