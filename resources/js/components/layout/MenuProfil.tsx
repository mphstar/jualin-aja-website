import { router } from '@inertiajs/react';
import { ChevronsUpDownIcon, LogOutIcon, SettingsIcon } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useAdmin } from '@/hooks/useAdmin';
import { inisial } from '@/lib/format';
import { useAuthStore } from '@/stores/authStore';

export function MenuProfil() {
    const admin = useAdmin();
    const keluar = useAuthStore((s) => s.keluar);
    const { isMobile } = useSidebar();
    const [konfirmasiKeluar, setKonfirmasiKeluar] = useState(false);

    if (!admin) {
        return null;
    }

    async function tanganiKeluar() {
        await keluar();
        toast.success('Anda telah keluar.');
        router.visit('/login', { replace: true });
    }

    return (
        <>
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <SidebarMenuButton
                                size="lg"
                                className="gap-2.5 data-[state=open]:bg-sidebar-accent"
                            >
                                {/* Bulat + bergaris, 32 px — bukan kotak 36 px seperti tebakan saya. */}
                                <Avatar className="size-8 shrink-0 rounded-full border border-border">
                                    <AvatarFallback className="rounded-full bg-muted text-xs font-semibold text-muted-foreground uppercase">
                                        {inisial(admin.nama)}
                                    </AvatarFallback>
                                </Avatar>
                                {/*
                  Keduanya WAJIB disembunyikan saat ciut. Tidak seperti <span>,
                  <div> dan ikon ini tidak ikut aturan bawaan shadcn — kalau
                  dibiarkan, keduanya mendorong avatar keluar dari kotak 40 px.
                */}
                                <div className="grid flex-1 text-left leading-tight group-data-[collapsible=icon]:hidden">
                                    <span className="truncate text-sm font-semibold">
                                        {admin.nama}
                                    </span>
                                    <span className="truncate text-xs text-muted-foreground">
                                        {admin.email}
                                    </span>
                                </div>
                                <ChevronsUpDownIcon className="ml-auto size-4 shrink-0 text-muted-foreground group-data-[collapsible=icon]:hidden" />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent
                            className="w-56"
                            side={isMobile ? 'bottom' : 'right'}
                            align="end"
                        >
                            <DropdownMenuLabel className="text-xs font-normal text-muted-foreground">
                                Masuk sebagai
                            </DropdownMenuLabel>
                            <DropdownMenuLabel className="pt-0 font-medium">
                                {admin.nama}
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                onClick={() => router.visit('/pengaturan')}
                            >
                                <SettingsIcon className="size-4" />
                                Pengaturan
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                variant="destructive"
                                onClick={() => setKonfirmasiKeluar(true)}
                            >
                                <LogOutIcon className="size-4" />
                                Keluar
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>

            <AlertDialog
                open={konfirmasiKeluar}
                onOpenChange={setKonfirmasiKeluar}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Keluar dari panel admin?
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            Anda perlu masuk kembali untuk mengakses data
                            pengguna dan langganan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction onClick={tanganiKeluar}>
                            Ya, keluar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
