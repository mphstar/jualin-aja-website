import { Loader2Icon } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { KesalahanApi } from '@/lib/api';
import type { DataTambahPengguna } from '@/lib/api';
import { DAFTAR_JENIS_USAHA, LABEL_JENIS_USAHA } from '@/lib/konstanta';
import type { DurasiPaket, JenisUsaha } from '@/types';

interface Props {
    buka: boolean;
    onTutup: () => void;
    onKirim: (data: DataTambahPengguna) => Promise<void>;
}

export function DialogTambahPengguna({ buka, onTutup, onKirim }: Props) {
    const [nama, setNama] = useState('');
    const [email, setEmail] = useState('');
    const [telepon, setTelepon] = useState('');
    const [namaToko, setNamaToko] = useState('');
    const [jenisUsaha, setJenisUsaha] = useState<JenisUsaha>('KAFE');
    const [kota, setKota] = useState('');
    const [alamat, setAlamat] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');

    const [adaLangganan, setAdaLangganan] = useState(false);
    const [durasi, setDurasi] = useState<DurasiPaket>('BULANAN');
    const [sumber, setSumber] = useState('PERPANJANGAN_MANUAL');
    const [lamaHari, setLamaHari] = useState(30);
    const [catatan, setCatatan] = useState('');

    const [mengirim, setMengirim] = useState(false);
    const [errorMsg, setErrorMsg] = useState<string | null>(null);

    function resetForm() {
        setNama('');
        setEmail('');
        setTelepon('');
        setNamaToko('');
        setJenisUsaha('KAFE');
        setKota('');
        setAlamat('');
        setPassword('');
        setPasswordConfirmation('');
        setAdaLangganan(false);
        setDurasi('BULANAN');
        setSumber('PERPANJANGAN_MANUAL');
        setLamaHari(30);
        setCatatan('');
        setErrorMsg(null);
    }

    function handleGantiDurasi(val: DurasiPaket) {
        setDurasi(val);
        if (val === 'TRIAL') {
            setLamaHari(14);
            setSumber('TRIAL');
        } else if (val === 'BULANAN') {
            setLamaHari(30);
            setSumber('PERPANJANGAN_MANUAL');
        } else if (val === 'SEMESTERAN') {
            setLamaHari(180);
            setSumber('PERPANJANGAN_MANUAL');
        } else if (val === 'TAHUNAN') {
            setLamaHari(365);
            setSumber('PERPANJANGAN_MANUAL');
        }
    }

    async function submit(e: React.FormEvent) {
        e.preventDefault();
        setErrorMsg(null);

        if (!nama || !email || !telepon || !namaToko || !kota || !password) {
            setErrorMsg('Harap isi semua kolom wajib.');
            return;
        }

        if (password.length < 8) {
            setErrorMsg('Kata sandi minimal 8 karakter.');
            return;
        }

        if (password !== passwordConfirmation) {
            setErrorMsg('Konfirmasi kata sandi tidak cocok.');
            return;
        }

        setMengirim(true);

        const payload: DataTambahPengguna = {
            nama: nama.trim(),
            email: email.trim(),
            telepon: telepon.trim(),
            namaToko: namaToko.trim(),
            jenisUsaha,
            kota: kota.trim(),
            alamat: alamat.trim() || undefined,
            password,
            password_confirmation: passwordConfirmation,
        };

        if (adaLangganan) {
            payload.langganan = {
                durasi,
                sumber,
                lamaHari,
                catatan: catatan.trim() || undefined,
            };
        }

        try {
            await onKirim(payload);
            toast.success('Pengguna berhasil ditambahkan', {
                description: `Akun toko ${namaToko} telah aktif.`,
            });
            resetForm();
            onTutup();
        } catch (e) {
            setErrorMsg(
                e instanceof KesalahanApi ? e.message : 'Gagal menambahkan pengguna.',
            );
        } finally {
            setMengirim(false);
        }
    }

    if (!buka) return null;

    return (
        <Dialog open onOpenChange={(o) => !o && !mengirim && onTutup()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Tambah Pelanggan Baru</DialogTitle>
                    <DialogDescription>
                        Daftarkan pemilik toko secara manual ke platform Jualin Aja.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4 py-2">
                    {errorMsg && (
                        <div className="rounded-md border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">
                            {errorMsg}
                        </div>
                    )}

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="nama">
                                Nama Lengkap <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="nama"
                                value={nama}
                                onChange={(e) => setNama(e.target.value)}
                                placeholder="Budi Santoso"
                                required
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">
                                Email <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                placeholder="budi@toko.id"
                                required
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="telepon">
                                No. Telepon <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="telepon"
                                value={telepon}
                                onChange={(e) => setTelepon(e.target.value)}
                                placeholder="08123456789"
                                required
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="namaToko">
                                Nama Toko <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="namaToko"
                                value={namaToko}
                                onChange={(e) => setNamaToko(e.target.value)}
                                placeholder="Kedai Kopi Kulo"
                                required
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="jenisUsaha">
                                Jenis Usaha <span className="text-destructive">*</span>
                            </Label>
                            <Select
                                value={jenisUsaha}
                                onValueChange={(v) => setJenisUsaha(v as JenisUsaha)}
                            >
                                <SelectTrigger id="jenisUsaha">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {DAFTAR_JENIS_USAHA.map((j) => (
                                        <SelectItem key={j} value={j}>
                                            {LABEL_JENIS_USAHA[j]}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="kota">
                                Kota <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="kota"
                                value={kota}
                                onChange={(e) => setKota(e.target.value)}
                                placeholder="Surabaya"
                                required
                            />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="alamat">Alamat Toko</Label>
                        <Textarea
                            id="alamat"
                            value={alamat}
                            onChange={(e) => setAlamat(e.target.value)}
                            placeholder="Jl. Pemuda No. 123"
                            rows={2}
                        />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                Kata Sandi <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                placeholder="Min 8 karakter"
                                required
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="passwordConfirmation">
                                Konfirmasi Kata Sandi <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="passwordConfirmation"
                                type="password"
                                value={passwordConfirmation}
                                onChange={(e) => setPasswordConfirmation(e.target.value)}
                                placeholder="Ulangi kata sandi"
                                required
                            />
                        </div>
                    </div>

                    <div className="mt-2 rounded-lg border p-4">
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="adaLangganan"
                                checked={adaLangganan}
                                onCheckedChange={(c) => setAdaLangganan(!!c)}
                            />
                            <Label htmlFor="adaLangganan" className="cursor-pointer font-medium">
                                Tambahkan Paket Langganan Awal
                            </Label>
                        </div>

                        {adaLangganan && (
                            <div className="mt-4 grid gap-4">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div className="grid gap-2">
                                        <Label htmlFor="durasi">Durasi Paket</Label>
                                        <Select
                                            value={durasi}
                                            onValueChange={(v) => handleGantiDurasi(v as DurasiPaket)}
                                        >
                                            <SelectTrigger id="durasi">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="TRIAL">Uji Coba (Trial)</SelectItem>
                                                <SelectItem value="BULANAN">1 Bulan</SelectItem>
                                                <SelectItem value="SEMESTERAN">6 Bulan</SelectItem>
                                                <SelectItem value="TAHUNAN">12 Bulan</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="sumber">Sumber</Label>
                                        <Select value={sumber} onValueChange={setSumber}>
                                            <SelectTrigger id="sumber">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="PERPANJANGAN_MANUAL">Perpanjangan Manual</SelectItem>
                                                <SelectItem value="TRIAL">Uji Coba</SelectItem>
                                                <SelectItem value="HADIAH">Hadiah</SelectItem>
                                                <SelectItem value="PEMBELIAN">Pembelian</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="lamaHari">Lama Berlaku (Hari)</Label>
                                        <Input
                                            id="lamaHari"
                                            type="number"
                                            min={1}
                                            max={3650}
                                            value={lamaHari}
                                            onChange={(e) => setLamaHari(parseInt(e.target.value) || 1)}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="catatan">Catatan Langganan</Label>
                                    <Input
                                        id="catatan"
                                        value={catatan}
                                        onChange={(e) => setCatatan(e.target.value)}
                                        placeholder="Misal: Paket promo pendaftaran pertama"
                                    />
                                </div>
                            </div>
                        )}
                    </div>

                    <DialogFooter className="mt-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onTutup}
                            disabled={mengirim}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={mengirim}>
                            {mengirim && <Loader2Icon className="mr-2 size-4 animate-spin" />}
                            {mengirim ? 'Menyimpan…' : 'Simpan Pengguna'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
