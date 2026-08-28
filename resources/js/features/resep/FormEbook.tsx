import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    FileTextIcon,
    ImageIcon,
    InfoIcon,
    Loader2Icon,
    UploadIcon,
    XIcon,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { useForm } from 'react-hook-form';
import { toast } from 'sonner';
import * as z from 'zod';
import { PageHeader } from '@/components/shared/PageHeader';
import { ErrorState } from '@/components/shared/StateTabel';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Form,
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Textarea } from '@/components/ui/textarea';
import { SampulEbook } from '@/features/resep/SampulEbook';
import { api, KesalahanApi } from '@/lib/api';
import type { MasukanEbook } from '@/lib/api';
import { formatUkuranFile } from '@/lib/format';
import {
    DAFTAR_JENIS_KONTEN,
    DAFTAR_KATEGORI_EBOOK,
    DAFTAR_KATEGORI_PROMPT,
    LABEL_JENIS_KONTEN,
    LABEL_KATEGORI_EBOOK,
    LABEL_KATEGORI_PROMPT,
} from '@/lib/konstanta';
import type {
    Ebook,
    JenisKonten,
    KategoriEbook,
    KategoriPrompt,
    StatusEbook,
} from '@/types';

const skema = z.object({
    jenis: z.enum(['RESEP', 'PROMPT'], {
        error: 'Jenis konten wajib dipilih.',
    }),
    judul: z
        .string()
        .min(5, { error: 'Judul minimal 5 karakter.' })
        .max(120, { error: 'Judul maksimal 120 karakter.' }),
    kategori: z
        .enum(
            [
                'MINUMAN',
                'MAKANAN_BERAT',
                'SNACK',
                'DESSERT',
                'BAKERY',
                'BUMBU_SAUS',
            ],
            { error: 'Kategori wajib dipilih.' },
        )
        .optional(),
    kategoriPrompt: z
        .enum(
            [
                'LOGO',
                'DESAIN_MENU',
                'POSTER_PROMOSI',
                'SOSIAL_MEDIA',
                'FOTO_PRODUK',
                'KEMASAN_PRODUK',
            ],
            { error: 'Kategori wajib dipilih.' },
        )
        .optional(),
    deskripsi: z
        .string()
        .min(20, { error: 'Deskripsi minimal 20 karakter.' })
        .max(600, { error: 'Deskripsi maksimal 600 karakter.' }),
    status: z.enum(['DRAF', 'TERBIT']),
    jumlahHalaman: z
        .string()
        .refine((v) => v === '' || Number(v) > 0, {
            error: 'Jumlah halaman harus lebih dari 0.',
        })
        .optional(),
}).superRefine((nilai, ctx) => {
    if (nilai.jenis === 'RESEP' && !nilai.kategori) {
        ctx.addIssue({
            code: z.ZodIssueCode.custom,
            path: ['kategori'],
            error: 'Kategori resep wajib dipilih.',
        });
    }

    if (nilai.jenis === 'PROMPT' && !nilai.kategoriPrompt) {
        ctx.addIssue({
            code: z.ZodIssueCode.custom,
            path: ['kategoriPrompt'],
            error: 'Kategori prompt wajib dipilih.',
        });
    }
});

type NilaiForm = z.infer<typeof skema>;

interface Berkas {
    /** Object URL — hanya untuk pratinjau sebelum dikirim. */
    url: string;
    nama: string;
    ukuran: number;
    /** Berkas asli; inilah yang benar-benar diunggah lewat multipart. */
    isi: File;
}

export function FormEbook({ id }: { id?: string }) {
    const ubah = Boolean(id);

    const [ebookLama, setEbookLama] = useState<Ebook | null>(null);
    const [memuat, setMemuat] = useState(ubah);
    const [gagalMuat, setGagalMuat] = useState<string | null>(null);
    const [mengirim, setMengirim] = useState(false);

    const [cover, setCover] = useState<Berkas | null>(null);
    const [pdf, setPdf] = useState<Berkas | null>(null);

    // URL objek harus dicabut manual, kalau tidak berkasnya menetap di memori.
    const urlSementara = useRef<string[]>([]);
    useEffect(
        () => () => urlSementara.current.forEach((u) => URL.revokeObjectURL(u)),
        [],
    );

    const form = useForm<NilaiForm>({
        resolver: zodResolver(skema),
        defaultValues: {
            jenis: 'RESEP',
            judul: '',
            kategori: 'MINUMAN',
            kategoriPrompt: 'LOGO',
            deskripsi: '',
            status: 'DRAF',
            jumlahHalaman: '',
        },
        mode: 'onTouched',
    });

    useEffect(() => {
        if (!id) {
            return;
        }

        let batal = false;
        setMemuat(true);
        api.ebook
            .ambilDetailEbook(id)
            .then(({ ebook }) => {
                if (batal) {
                    return;
                }

                setEbookLama(ebook);
                form.reset({
                    jenis: ebook.jenis,
                    judul: ebook.judul,
                    // API mengirim `null` untuk kategori yang tidak dipakai
                    // (mis. RESEP → kategoriPrompt = null). `z.optional()`
                    // hanya menerima `undefined`, bukan `null`, jadi null di sini
                    // membuat validasi selalu gagal — dan karena field tsb tidak
                    // dirender, kegagalannya tak terlihat: tombol simpan tanpa
                    // pesan apa pun. Konversikan dulu ke `undefined`.
                    kategori: ebook.kategori ?? undefined,
                    kategoriPrompt: ebook.kategoriPrompt ?? undefined,
                    deskripsi: ebook.deskripsi,
                    status: ebook.status,
                    jumlahHalaman: ebook.jumlahHalaman
                        ? String(ebook.jumlahHalaman)
                        : '',
                });
            })
            .catch((e: unknown) =>
                setGagalMuat(
                    e instanceof KesalahanApi
                        ? e.message
                        : 'Ebook tidak ditemukan.',
                ),
            )
            .finally(() => !batal && setMemuat(false));

        return () => {
            batal = true;
        };
    }, [id, form]);

    function pilihBerkas(
        berkas: File | undefined,
        simpan: (b: Berkas | null) => void,
    ) {
        if (!berkas) {
            return;
        }

        const url = URL.createObjectURL(berkas);
        urlSementara.current.push(url);
        simpan({ url, nama: berkas.name, ukuran: berkas.size, isi: berkas });
    }

    async function kirim(nilai: NilaiForm) {
        setMengirim(true);
        const masukan: MasukanEbook = {
            jenis: nilai.jenis as JenisKonten,
            judul: nilai.judul.trim(),
            kategori: nilai.kategori as KategoriEbook | undefined,
            kategoriPrompt: nilai.kategoriPrompt as KategoriPrompt | undefined,
            deskripsi: nilai.deskripsi.trim(),
            status: nilai.status as StatusEbook,
            // Hanya unggahan baru yang dikirim; berkas lama dibiarkan apa adanya
            // oleh server bila field-nya tidak ada.
            cover: cover?.isi,
            berkas: pdf?.isi,
            jumlahHalaman: nilai.jumlahHalaman
                ? Number(nilai.jumlahHalaman)
                : undefined,
        };

        try {
            const hasil = ubah
                ? await api.ebook.ubahEbook(id!, masukan)
                : await api.ebook.tambahEbook(masukan);
            toast.success(ubah ? 'Ebook diperbarui' : 'Ebook ditambahkan', {
                description:
                    hasil.status === 'TERBIT'
                        ? `"${hasil.judul}" sudah bisa diunduh pelanggan berlangganan.`
                        : `"${hasil.judul}" tersimpan sebagai draf.`,
            });
            router.visit(`/resep/${hasil.id}`);
        } catch (e) {
            toast.error(
                e instanceof KesalahanApi
                    ? e.message
                    : 'Gagal menyimpan ebook.',
            );
        } finally {
            setMengirim(false);
        }
    }

    if (gagalMuat) {
        return (
            <div className="rounded-lg border">
                <ErrorState
                    pesan={gagalMuat}
                    onCobaLagi={() => router.visit('/resep')}
                />
            </div>
        );
    }

    if (memuat) {
        return (
            <div className="space-y-4">
                <Skeleton className="h-8 w-56" />
                <Skeleton className="h-96 w-full" />
            </div>
        );
    }

    const jenisTerpilih = form.watch('jenis') as JenisKonten;
    const kategoriTerpilih = form.watch('kategori') as KategoriEbook | undefined;
    const kategoriPromptTerpilih = form.watch('kategoriPrompt') as
        | KategoriPrompt
        | undefined;
    const judulTerpilih = form.watch('judul');

    return (
        <>
            <Button
                variant="ghost"
                size="sm"
                className="mb-3 -ml-2 text-muted-foreground"
                onClick={() =>
                    router.visit(ubah && id ? `/resep/${id}` : '/resep')
                }
            >
                <ArrowLeftIcon className="size-4" />
                {ubah ? 'Kembali ke detail ebook' : 'Kembali ke katalog'}
            </Button>

            <PageHeader
                judul={ubah ? 'Ubah ebook' : 'Tambah ebook'}
                keterangan={
                    ubah
                        ? 'Perbarui metadata atau ganti berkas ebook ini.'
                        : 'Unggah ebook resep baru ke katalog pelanggan.'
                }
            />

            <Form {...form}>
                <form
                    onSubmit={form.handleSubmit(kirim)}
                    className="grid gap-4 lg:grid-cols-3"
                    noValidate
                >
                    <div className="space-y-4 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Informasi konten
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4">
                                <FormField
                                    control={form.control}
                                    name="jenis"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Jenis konten</FormLabel>
                                            <FormControl>
                                                <RadioGroup
                                                    value={field.value}
                                                    onValueChange={(v) => {
                                                        field.onChange(v);
                                                        form.setValue(
                                                            'kategori',
                                                            v === 'RESEP'
                                                                ? 'MINUMAN'
                                                                : undefined,
                                                        );
                                                        form.setValue(
                                                            'kategoriPrompt',
                                                            v === 'PROMPT'
                                                                ? 'LOGO'
                                                                : undefined,
                                                        );
                                                    }}
                                                    className="flex gap-2"
                                                >
                                                    {DAFTAR_JENIS_KONTEN.map(
                                                        (j) => (
                                                            <Label
                                                                key={j}
                                                                htmlFor={`jenis-${j}`}
                                                                className="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-md border p-3 font-normal hover:bg-accent has-[[data-state=checked]]:border-primary"
                                                            >
                                                                <RadioGroupItem
                                                                    value={j}
                                                                    id={`jenis-${j}`}
                                                                />
                                                                {
                                                                    LABEL_JENIS_KONTEN[
                                                                        j
                                                                    ]
                                                                }
                                                            </Label>
                                                        ),
                                                    )}
                                                </RadioGroup>
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="judul"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Judul</FormLabel>
                                            <FormControl>
                                                <Input
                                                    placeholder={
                                                        jenisTerpilih ===
                                                        'PROMPT'
                                                            ? 'Mis. Prompt Logo Usaha Kekinian'
                                                            : 'Mis. 50 Resep Minuman Kekinian'
                                                    }
                                                    {...field}
                                                />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                {jenisTerpilih === 'PROMPT' ? (
                                    <FormField
                                        control={form.control}
                                        name="kategoriPrompt"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>Kategori</FormLabel>
                                                <Select
                                                    value={field.value}
                                                    onValueChange={
                                                        field.onChange
                                                    }
                                                >
                                                    <FormControl>
                                                        <SelectTrigger className="w-full">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                    </FormControl>
                                                    <SelectContent>
                                                        {DAFTAR_KATEGORI_PROMPT.map(
                                                            (k) => (
                                                                <SelectItem
                                                                    key={k}
                                                                    value={k}
                                                                >
                                                                    {
                                                                        LABEL_KATEGORI_PROMPT[
                                                                            k
                                                                        ]
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                ) : (
                                    <FormField
                                        control={form.control}
                                        name="kategori"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>Kategori</FormLabel>
                                                <Select
                                                    value={field.value}
                                                    onValueChange={
                                                        field.onChange
                                                    }
                                                >
                                                    <FormControl>
                                                        <SelectTrigger className="w-full">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                    </FormControl>
                                                    <SelectContent>
                                                        {DAFTAR_KATEGORI_EBOOK.map(
                                                            (k) => (
                                                                <SelectItem
                                                                    key={k}
                                                                    value={k}
                                                                >
                                                                    {
                                                                        LABEL_KATEGORI_EBOOK[
                                                                            k
                                                                        ]
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                )}

                                <FormField
                                    control={form.control}
                                    name="deskripsi"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Deskripsi</FormLabel>
                                            <FormControl>
                                                <Textarea
                                                    rows={5}
                                                    placeholder="Jelaskan isi ebook: jumlah resep, untuk siapa, dan apa yang didapat pembaca."
                                                    {...field}
                                                />
                                            </FormControl>
                                            <FormDescription>
                                                Deskripsi ini yang dibaca
                                                pelanggan di aplikasi POS.
                                            </FormDescription>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="jumlahHalaman"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>
                                                Jumlah halaman (opsional)
                                            </FormLabel>
                                            <FormControl>
                                                <Input
                                                    type="number"
                                                    min={1}
                                                    className="w-40"
                                                    placeholder="80"
                                                    {...field}
                                                />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Berkas
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4">
                                <div className="flex items-start gap-3 rounded-md border bg-muted/50 p-3 text-sm text-muted-foreground">
                                    <InfoIcon className="mt-0.5 size-4 shrink-0" />
                                    <p>
                                        Sampul maksimal 4&nbsp;MB, berkas ebook
                                        PDF maksimal 50&nbsp;MB. Unggahan baru
                                        menggantikan berkas lama.
                                    </p>
                                </div>

                                <PilihBerkas
                                    id="berkas-cover"
                                    label="Sampul (gambar)"
                                    accept="image/*"
                                    ikon={<ImageIcon className="size-4" />}
                                    berkas={cover}
                                    onPilih={(f) => pilihBerkas(f, setCover)}
                                    onHapus={() => setCover(null)}
                                    keterangan={
                                        ebookLama?.coverUrl && !cover
                                            ? 'Sampul lama tetap dipakai bila tidak diganti.'
                                            : undefined
                                    }
                                />

                                <PilihBerkas
                                    id="berkas-pdf"
                                    label="Berkas ebook (PDF)"
                                    accept="application/pdf"
                                    ikon={<FileTextIcon className="size-4" />}
                                    berkas={pdf}
                                    onPilih={(f) => pilihBerkas(f, setPdf)}
                                    onHapus={() => setPdf(null)}
                                    keterangan={
                                        ebookLama?.namaFile && !pdf
                                            ? `Berkas saat ini: ${ebookLama.namaFile}`
                                            : undefined
                                    }
                                />
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Status terbit
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <FormField
                                    control={form.control}
                                    name="status"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormControl>
                                                <RadioGroup
                                                    value={field.value}
                                                    onValueChange={
                                                        field.onChange
                                                    }
                                                    className="gap-2"
                                                >
                                                    <Label
                                                        htmlFor="status-DRAF"
                                                        className="flex cursor-pointer items-start gap-3 rounded-md border p-3 font-normal hover:bg-accent has-[[data-state=checked]]:border-primary"
                                                    >
                                                        <RadioGroupItem
                                                            value="DRAF"
                                                            id="status-DRAF"
                                                            className="mt-0.5"
                                                        />
                                                        <span>
                                                            <span className="block font-medium">
                                                                Draf
                                                            </span>
                                                            <span className="block text-xs text-muted-foreground">
                                                                Tersimpan, tapi
                                                                belum terlihat
                                                                pelanggan.
                                                            </span>
                                                        </span>
                                                    </Label>
                                                    <Label
                                                        htmlFor="status-TERBIT"
                                                        className="flex cursor-pointer items-start gap-3 rounded-md border p-3 font-normal hover:bg-accent has-[[data-state=checked]]:border-primary"
                                                    >
                                                        <RadioGroupItem
                                                            value="TERBIT"
                                                            id="status-TERBIT"
                                                            className="mt-0.5"
                                                        />
                                                        <span>
                                                            <span className="block font-medium">
                                                                Terbit
                                                            </span>
                                                            <span className="block text-xs text-muted-foreground">
                                                                Bisa diunduh
                                                                semua pelanggan
                                                                berlangganan
                                                                aktif.
                                                            </span>
                                                        </span>
                                                    </Label>
                                                </RadioGroup>
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Pratinjau
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="aspect-[16/10] overflow-hidden rounded-md border">
                                    <SampulEbook
                                        jenis={jenisTerpilih}
                                        kategori={kategoriTerpilih}
                                        kategoriPrompt={kategoriPromptTerpilih}
                                        coverUrl={
                                            cover?.url ?? ebookLama?.coverUrl
                                        }
                                        judul={judulTerpilih || 'Konten baru'}
                                    />
                                </div>
                                <p className="line-clamp-2 text-sm font-medium">
                                    {judulTerpilih ||
                                        'Judul konten akan muncul di sini'}
                                </p>
                            </CardContent>
                        </Card>

                        <div className="flex flex-col gap-2">
                            <Button type="submit" disabled={mengirim}>
                                {mengirim && (
                                    <Loader2Icon className="size-4 animate-spin" />
                                )}
                                {mengirim ? 'Menyimpan…' : 'Simpan ebook'}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() =>
                                    router.visit(
                                        ubah && id ? `/resep/${id}` : '/resep',
                                    )
                                }
                                disabled={mengirim}
                            >
                                Batal
                            </Button>
                        </div>
                    </div>
                </form>
            </Form>
        </>
    );
}

function PilihBerkas({
    id,
    label,
    accept,
    ikon,
    berkas,
    onPilih,
    onHapus,
    keterangan,
}: {
    id: string;
    label: string;
    accept: string;
    ikon: ReactNode;
    berkas: Berkas | null;
    onPilih: (berkas: File | undefined) => void;
    onHapus: () => void;
    keterangan?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>

            {berkas ? (
                <div className="flex items-center gap-3 rounded-md border p-3">
                    <span className="text-muted-foreground">{ikon}</span>
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-medium">
                            {berkas.nama}
                        </p>
                        <p className="angka-tabular text-xs text-muted-foreground">
                            {formatUkuranFile(berkas.ukuran)}
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="size-8"
                        onClick={onHapus}
                        aria-label={`Hapus ${label}`}
                    >
                        <XIcon className="size-4" />
                    </Button>
                </div>
            ) : (
                <Label
                    htmlFor={id}
                    className="flex cursor-pointer items-center justify-center gap-2 rounded-md border border-dashed p-4 text-sm font-normal text-muted-foreground transition-colors hover:bg-accent"
                >
                    <UploadIcon className="size-4" />
                    Pilih berkas
                </Label>
            )}

            <Input
                id={id}
                type="file"
                accept={accept}
                className="sr-only"
                onChange={(e) => onPilih(e.target.files?.[0])}
            />

            {keterangan && (
                <p className="text-xs text-muted-foreground">{keterangan}</p>
            )}
        </div>
    );
}
