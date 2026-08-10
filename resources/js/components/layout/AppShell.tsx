/* Hallmark · genre: modern-minimal · macrostructure: Workbench (app shell)
 * nav: N3 side-rail · knobs: floating-card, grouped-labels, pill-active, no-CTA
 * theme: studied-DNA (source: image) · paper oklch(100% 0 0) · card oklch(100% 0 0)
 * accent: netral — batas dari garis rambut, bukan beda keterangan atau bayangan
 * header: none (desktop) · judul halaman berperan sebagai kepala
 * studied: yes · pre-emit critique: P5 H5 E4 S5 R5 V4
 */
import { Link, usePage } from '@inertiajs/react';
import { ChefHatIcon, PanelLeftOpenIcon } from 'lucide-react';
import { useEffect } from 'react';
import type { CSSProperties, ReactNode } from 'react';
import { MenuProfil } from '@/components/layout/MenuProfil';
import { GRUP_NAVIGASI, cariNavigasi } from '@/components/layout/navigasi';
import { TombolTema } from '@/components/layout/TombolTema';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarRail,
    SidebarTrigger,
    useSidebar,
} from '@/components/ui/sidebar';
import { usePengaturanStore } from '@/stores/pengaturanStore';

/**
 * Nilai di bawah DISALIN dari kode sumber referensi, bukan ditaksir dari
 * tangkapan layar — tiga putaran pertama saya menebak dan tiga-tiganya
 * meleset. Sumber: `components/ui/sidebar.tsx` (varian tombol menu) dan
 * `components/AppSidebar.tsx` (kerangka) di repo yang Anda tunjuk.
 *
 *   lebar sidebar ........ 18rem / 288 px  → isi kartu 256 px
 *   selokan kartu ........ 16 px  (p-4)
 *   tinggi baris nav ..... 40 px  (h-10)
 *   teks nav ............. 15 px  (text-[0.9375rem])
 *   ikon nav ............. 18 px  (size-[1.125rem])
 *   warna nav non-aktif .. sidebar-foreground/75
 *   ikon nav aktif ....... text-brand  ← indigo, satu-satunya aksen
 *   jarak antar baris .... 6 px   (gap-1.5)
 */
const BARIS_NAV =
    'h-10 gap-3 rounded-control px-3 text-[0.9375rem] ' +
    'text-sidebar-foreground/75 [&>svg]:size-[1.125rem] ' +
    // Hanya ikon item aktif yang berwarna. Inilah satu-satunya tempat
    // warna merek muncul di seluruh shell.
    'data-[active=true]:font-semibold data-[active=true]:[&>svg]:text-brand';

/**
 * Baris merek: identitas kiri, kendali tampilan kanan.
 *
 * Saat ciut, logo BERUBAH JADI tombol pelebar — ini cara referensi, dan
 * jalan keluar dari masalah nyata: tombol pemicu ikut tersembunyi saat
 * ciut, menyisakan `SidebarRail` (garis tak terlihat di tepi kartu)
 * sebagai satu-satunya jalan kembali. Praktis tidak bisa ditemukan.
 */
function BarisMerek() {
    const { state, isMobile, toggleSidebar } = useSidebar();

    if (state === 'collapsed' && !isMobile) {
        return (
            <button
                type="button"
                onClick={toggleSidebar}
                title="Lebarkan sidebar"
                aria-label="Lebarkan sidebar"
                className="group/logo mx-auto flex size-10 shrink-0 items-center justify-center rounded-control hover:bg-muted/50"
            >
                <img src="/logo.png" alt="JualinAja" className="size-8 object-contain group-hover/logo:hidden" />
                <PanelLeftOpenIcon className="hidden size-[1.125rem] group-hover/logo:block" />
            </button>
        );
    }

    return (
        <div className="flex items-center gap-2.5 border-b border-sidebar-border pb-3 group-data-[collapsible=icon]:border-b-0">
            <Link
                href="/dasbor"
                className="flex min-w-0 flex-1 items-center gap-2.5 rounded-control"
            >
                <img
                    src="/logo.png"
                    alt="JualinAja Logo"
                    className="size-9 shrink-0 object-contain"
                />
                <span className="truncate text-sm font-bold group-data-[collapsible=icon]:hidden">
                    JualinAja Admin
                </span>
            </Link>

            {/*
        Toggle tema & tombol ciut tinggal di sini, bukan di topbar — begitu
        header dihapus, keduanya butuh rumah tetap yang tidak ikut menggeser
        konten halaman.
      */}
            <div className="flex shrink-0 items-center gap-0.5 group-data-[collapsible=icon]:hidden">
                <TombolTema />
                <SidebarTrigger className="size-7 text-muted-foreground" />
            </div>
        </div>
    );
}

/**
 * Layout persisten Inertia: dipasang lewat `Halaman.layout` di tiap komponen
 * halaman (resources/js/pages), jadi sidebar dan state-nya bertahan lintas
 * kunjungan — tidak dibongkar-pasang setiap pindah halaman.
 */
export function AppShell({ children }: { children: ReactNode }) {
    // `usePage().url` membawa query string; navigasi dicocokkan per path saja.
    const pathname = usePage().url.split('?')[0]!;
    const aktif = cariNavigasi(pathname);

    /*
     * Harga paket dimuat di sini, bukan di tiap halaman yang memerlukannya.
     * Layout ini persisten, jadi satu permintaan per sesi cukup — dan dialog
     * perpanjang tidak perlu mengurus pemuatan datanya sendiri.
     */
    const muatPengaturan = usePengaturanStore((s) => s.muat);
    const sudahDimuat = usePengaturanStore((s) => s.sudahDimuat);
    useEffect(() => {
        if (!sudahDimuat) {
            void muatPengaturan();
        }
    }, [sudahDimuat, muatPengaturan]);

    return (
        /* 18rem/288 px dengan selokan 16 px → isi kartu 256 px (nilai referensi). */
        <SidebarProvider
            style={
                {
                    '--sidebar-width': '18rem',
                    '--sidebar-width-icon': '3.5rem',
                } as CSSProperties
            }
        >
            {/*
        `variant="floating"` melepaskan sidebar dari tepi layar jadi kartu
        tersendiri. `rounded-xl` menyamakan radiusnya dengan <Card>, dan
        bayangan dimatikan supaya kedalaman tetap datang dari beda keterangan.
      */}
            <Sidebar
                collapsible="icon"
                variant="floating"
                // Selokan 16 px kini diatur primitif (varian floating), bukan di sini —
                // supaya lebar saat ciut ikut terhitung dari angka yang sama.
                className="[&_[data-slot=sidebar-inner]]:rounded-panel [&_[data-slot=sidebar-inner]]:shadow-none"
            >
                <SidebarHeader className="gap-0 px-3 pt-3 pb-0 group-data-[collapsible=icon]:px-2">
                    <BarisMerek />
                </SidebarHeader>

                {/* Jarak antar grup datang dari `pb-3` tiap grup, bukan dari gap. */}
                <SidebarContent className="gap-0 overflow-x-hidden px-3 pt-4 group-data-[collapsible=icon]:px-2 group-data-[collapsible=icon]:pt-3">
                    {GRUP_NAVIGASI.map((grup) => (
                        <SidebarGroup
                            key={grup.label}
                            className="px-0 pt-0 pb-3"
                        >
                            {/* Label lebih terang daripada item nav — ia penanda, bukan tujuan. */}
                            <SidebarGroupLabel className="text-[0.6875rem] font-medium tracking-wider text-muted-foreground uppercase">
                                {grup.label}
                            </SidebarGroupLabel>
                            <SidebarGroupContent>
                                <SidebarMenu className="gap-1.5">
                                    {grup.item.map((item) => (
                                        <SidebarMenuItem key={item.href}>
                                            <SidebarMenuButton
                                                asChild
                                                tooltip={item.judul}
                                                isActive={
                                                    aktif?.href === item.href
                                                }
                                                className={BARIS_NAV}
                                            >
                                                <Link href={item.href}>
                                                    <item.ikon />
                                                    <span>{item.judul}</span>
                                                </Link>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    ))}
                                </SidebarMenu>
                            </SidebarGroupContent>
                        </SidebarGroup>
                    ))}
                </SidebarContent>

                <SidebarFooter className="gap-0 px-3 pb-3 group-data-[collapsible=icon]:px-2">
                    <div className="border-t border-sidebar-border pt-3">
                        <p className="mb-1.5 px-1 text-[0.6875rem] font-medium tracking-wider text-muted-foreground uppercase group-data-[collapsible=icon]:hidden">
                            Akun
                        </p>
                        <MenuProfil />
                    </div>
                </SidebarFooter>

                <SidebarRail />
            </Sidebar>

            <SidebarInset className="min-w-0 bg-transparent">
                {/*
          Desktop tidak punya header sama sekali — judul halaman yang bekerja
          sebagai kepala. Bar ini hanya untuk layar kecil, tempat sidebar
          berubah jadi drawer dan pemicunya harus tetap terjangkau.
        */}
                <header className="sticky top-0 z-10 flex h-14 shrink-0 items-center gap-3 border-b bg-background/85 px-4 backdrop-blur md:hidden">
                    <SidebarTrigger className="-ml-1" />
                    <span className="truncate text-sm font-medium">
                        {aktif?.judul ?? 'Admin POS'}
                    </span>
                </header>

                {/* Padding isi halaman disalin dari `PageShell` referensi:
            px 20→32→40, pt 28, pb 64 (ruang napas di kaki halaman). */}
                <main className="min-w-0 flex-1 overflow-x-clip px-5 pt-7 pb-16 sm:px-8 lg:px-10">
                    {children}
                </main>
            </SidebarInset>
        </SidebarProvider>
    );
}
