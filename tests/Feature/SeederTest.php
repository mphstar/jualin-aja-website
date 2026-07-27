<?php

declare(strict_types=1);

use App\Enums\StatusEbook;
use App\Enums\SumberLangganan;
use App\Models\Ebook;
use App\Models\Langganan;
use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Models\Pengaturan;
use App\Models\PosUser;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PenggunaLanggananSeeder;
use Illuminate\Support\Facades\Hash;

/*
 * Seed adalah data yang dilihat setiap kali panel dibuka saat pengembangan.
 * Kalau sebarannya melenceng, dasbor dan tab status memberi gambaran yang salah
 * tentang tampilan aplikasi — dan itu baru ketahuan jauh belakangan.
 */

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('membuat admin yang bisa dipakai masuk', function (): void {
    $admin = User::query()->sole();

    expect($admin->email)->toBe(DatabaseSeeder::EMAIL_ADMIN)
        ->and(Hash::check('admin123', $admin->password))->toBeTrue();
});

it('menghasilkan sebaran status persis seperti yang dijanjikan', function (): void {
    $terhitung = PosUser::query()->get()
        ->countBy(fn (PosUser $u): string => $u->status()->value)
        ->all();

    ksort($terhitung);
    $diharapkan = PenggunaLanggananSeeder::sebaran();
    ksort($diharapkan);

    expect($terhitung)->toBe($diharapkan)
        ->and(PosUser::query()->count())->toBe(array_sum($diharapkan));
});

it('tidak menghasilkan tanggal daftar di masa depan', function (): void {
    expect(PosUser::query()->where('tanggal_daftar', '>', now())->count())->toBe(0);
});

it('tidak menyisakan sisa hari melebihi panjang paketnya', function (): void {
    // Bug nyata di versi mock: "paket 1 Bulan, sisa 378 hari".
    $aneh = PosUser::query()->with('langgananBerlaku')->get()
        ->filter(function (PosUser $posUser): bool {
            $berlaku = $posUser->langgananBerlaku;

            return $berlaku !== null
                && $berlaku->durasi->bulan() > 0
                && $posUser->sisaHari() > $berlaku->durasi->bulan() * 30;
        });

    expect($aneh)->toBeEmpty();
});

it('memberi tiap toko satu siklus uji coba yang mendahului siklus berbayar', function (): void {
    foreach (PosUser::query()->with('langganan')->get() as $posUser) {
        $trial = $posUser->langganan->firstWhere('sumber', SumberLangganan::Trial);

        expect($trial)->not->toBeNull();

        foreach ($posUser->langganan->where('sumber', '!=', SumberLangganan::Trial) as $berbayar) {
            expect($berbayar->tanggal_mulai->greaterThanOrEqualTo($trial->tanggal_berakhir))->toBeTrue();
        }
    }
});

it('menyegarkan kolom ringkasan langganan tiap toko', function (): void {
    foreach (PosUser::query()->with('langganan')->get() as $posUser) {
        $terjauh = $posUser->langganan->sortByDesc('tanggal_berakhir')->first();

        expect($posUser->langganan_berlaku_id)->toBe($terjauh->id)
            ->and($posUser->langganan_berakhir_pada->toIso8601String())
            ->toBe($terjauh->tanggal_berakhir->toIso8601String());
    }
});

it('mengisi katalog dengan 12 ebook dan riwayat unduhannya', function (): void {
    expect(Ebook::query()->count())->toBe(12)
        ->and(Ebook::query()->where('status', StatusEbook::Terbit->value)->count())->toBe(9)
        // Draf belum pernah terbit, jadi tidak boleh punya unduhan.
        ->and(Ebook::query()->where('status', StatusEbook::Draf->value)->sum('jumlah_unduhan'))->toBe(0);

    foreach (Ebook::query()->withCount('unduhan')->get() as $ebook) {
        expect($ebook->jumlah_unduhan)->toBe($ebook->unduhan_count);
    }
});

it('menurunkan log aktivitas dari data yang benar-benar ada', function (): void {
    $log = LogAktivitas::query()->whereNotNull('target_id')->get();

    expect($log)->not->toBeEmpty();

    foreach ($log as $entri) {
        $ada = match ($entri->target_tipe->value) {
            'USER', 'LANGGANAN' => PosUser::query()->whereKey($entri->target_id)->exists(),
            'EBOOK' => Ebook::query()->whereKey($entri->target_id)->exists(),
            'PEMBAYARAN' => Pembayaran::query()->whereKey($entri->target_id)->exists(),
            default => true,
        };

        // Log yang menunjuk objek fiktif langsung ketahuan begitu diklik.
        expect($ada)->toBeTrue();
    }
});

it('menyimpan harga paket awal', function (): void {
    expect(Pengaturan::ambil(Pengaturan::KUNCI_HARGA_PAKET))
        ->toBe(['TRIAL' => 0, 'BULANAN' => 99_000, 'SEMESTERAN' => 499_000, 'TAHUNAN' => 899_000]);
});

it('menautkan tiap pembayaran ke siklus langganan yang sah', function (): void {
    $idLangganan = Langganan::query()->pluck('id')->all();

    foreach (Pembayaran::query()->get() as $pembayaran) {
        expect($pembayaran->langganan_id)->toBeIn($idLangganan);
    }
});
