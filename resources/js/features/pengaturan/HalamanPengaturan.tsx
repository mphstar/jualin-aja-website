import { PageHeader } from '@/components/shared/PageHeader';
import { FormHargaPaket } from '@/features/pengaturan/FormHargaPaket';
import { FormPengaturanMayar } from '@/features/pengaturan/FormPengaturanMayar';
import { FormProfil } from '@/features/pengaturan/FormProfil';
import { PilihanTema } from '@/features/pengaturan/PilihanTema';

export function HalamanPengaturan() {
    return (
        <>
            <PageHeader
                judul="Pengaturan"
                keterangan="Profil admin, tampilan panel, harga paket, dan pembayaran."
            />

            <div className="grid max-w-3xl gap-4">
                <FormProfil />
                <PilihanTema />
                <FormPengaturanMayar />
                <FormHargaPaket />
            </div>
        </>
    );
}
