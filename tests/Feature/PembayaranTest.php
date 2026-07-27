<?php

declare(strict_types=1);

use App\Enums\DurasiPaket;
use App\Enums\JenisAksi;
use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Enums\SumberLangganan;
use App\Models\Langganan;
use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Models\PosUser;
use Carbon\CarbonImmutable;

beforeEach(fn () => admin());

it('menyaring pembayaran menurut status, metode, dan rentang tanggal', function (): void {
    $posUser = PosUser::factory()->create();

    Pembayaran::factory()->create([
        'pos_user_id' => $posUser->id,
        'metode' => MetodePembayaran::Qris,
        'tanggal' => CarbonImmutable::parse('2026-03-10'),
    ]);
    Pembayaran::factory()->menunggu()->create([
        'pos_user_id' => $posUser->id,
        'metode' => MetodePembayaran::TransferBank,
        'tanggal' => CarbonImmutable::parse('2026-06-20'),
    ]);

    expect($this->getJson('/api/v1/pembayaran?status=MENUNGGU')->assertOk()->json('total'))->toBe(1)
        ->and($this->getJson('/api/v1/pembayaran?metode=QRIS')->assertOk()->json('total'))->toBe(1)
        ->and($this->getJson('/api/v1/pembayaran?dari=2026-06-01')->assertOk()->json('total'))->toBe(1)
        // Batas atas ikut memuat transaksi sepanjang hari itu.
        ->and($this->getJson('/api/v1/pembayaran?sampai=2026-06-20')->assertOk()->json('total'))->toBe(2);
});

it('mencari invoice lewat nomor maupun nama toko', function (string $kueri): void {
    $posUser = PosUser::factory()->create(['nama' => 'Budi Santoso', 'nama_toko' => 'Kopi Senja']);
    Pembayaran::factory()->create(['pos_user_id' => $posUser->id, 'nomor_invoice' => 'INV/2026/0007']);
    Pembayaran::factory()->create(['pos_user_id' => PosUser::factory()->create()->id]);

    expect($this->getJson('/api/v1/pembayaran?cari='.urlencode($kueri))->assertOk()->json('total'))->toBe(1);
})->with(['INV/2026/0007', 'Kopi Senja', 'Budi']);

it('meringkas pendapatan bulan berjalan dan antrean yang perlu ditindak', function (): void {
    $posUser = PosUser::factory()->create();

    Pembayaran::factory()->create(['pos_user_id' => $posUser->id, 'nominal' => 499_000, 'tanggal' => now()]);
    Pembayaran::factory()->create(['pos_user_id' => $posUser->id, 'nominal' => 99_000, 'tanggal' => now()]);
    // Bulan lalu — tidak boleh ikut terhitung.
    Pembayaran::factory()->create(['pos_user_id' => $posUser->id, 'nominal' => 899_000, 'tanggal' => now()->subMonth()->startOfMonth()]);
    Pembayaran::factory()->menunggu()->create(['pos_user_id' => $posUser->id]);
    Pembayaran::factory()->gagal()->count(2)->create(['pos_user_id' => $posUser->id]);

    $this->getJson('/api/v1/pembayaran/ringkasan')->assertOk()->assertExactJson([
        'totalLunasBulanIni' => 598_000,
        'jumlahMenunggu' => 1,
        'jumlahGagal' => 2,
    ]);
});

it('menyusun detail invoice beserta data tokonya', function (): void {
    $posUser = PosUser::factory()->create(['nama_toko' => 'Warung Barokah']);
    $pembayaran = Pembayaran::factory()->create(['pos_user_id' => $posUser->id]);

    $this->getJson("/api/v1/pembayaran/{$pembayaran->id}")
        ->assertOk()
        ->assertJsonPath('pembayaran.namaToko', 'Warung Barokah')
        ->assertJsonPath('user.namaToko', 'Warung Barokah');
});

it('memperpanjang langganan saat invoice ditandai lunas', function (): void {
    $posUser = PosUser::factory()->create();
    Langganan::factory()->berakhirDalam(10)->create([
        'pos_user_id' => $posUser->id,
        'sumber' => SumberLangganan::Pembelian,
    ]);
    $pembayaran = Pembayaran::factory()->menunggu()->create([
        'pos_user_id' => $posUser->id,
        'durasi' => DurasiPaket::Semesteran,
        'nomor_invoice' => 'INV/2026/0042',
    ]);

    $sebelum = $posUser->refresh()->langganan_berakhir_pada;

    $this->postJson("/api/v1/pembayaran/{$pembayaran->id}/tandai-lunas")
        ->assertOk()
        ->assertJsonPath('status', StatusPembayaran::Lunas->value);

    $sesudah = $posUser->refresh()->langganan_berakhir_pada;

    expect($sesudah->toDateString())->toBe($sebelum->addMonths(6)->toDateString())
        ->and($pembayaran->refresh()->dibayar_pada)->not->toBeNull()
        // Invoice ditautkan ke siklus yang lahir darinya.
        ->and($pembayaran->langganan_id)->toBe($posUser->langganan_berlaku_id);
});

it('mencatat pelunasan dan perpanjangannya sebagai dua entri log', function (): void {
    $posUser = PosUser::factory()->create();
    $pembayaran = Pembayaran::factory()->menunggu()->create([
        'pos_user_id' => $posUser->id,
        'nomor_invoice' => 'INV/2026/0042',
    ]);

    $this->postJson("/api/v1/pembayaran/{$pembayaran->id}/tandai-lunas")->assertOk();

    expect(LogAktivitas::query()->where('aksi', JenisAksi::PembayaranLunas->value)->sole()->target_label)
        ->toBe('INV/2026/0042')
        ->and(LogAktivitas::query()->where('aksi', JenisAksi::LanggananPerpanjang->value)->sole()->deskripsi)
        ->toContain($posUser->nama_toko);
});

it('menolak pelunasan invoice yang sudah lunas', function (): void {
    $pembayaran = Pembayaran::factory()->create(['pos_user_id' => PosUser::factory()->create()->id]);

    $this->postJson("/api/v1/pembayaran/{$pembayaran->id}/tandai-lunas")
        ->assertStatus(422)
        ->assertJsonPath('message', 'Invoice ini sudah berstatus lunas.');
});

it('menandai invoice gagal dan mencatatnya', function (): void {
    $pembayaran = Pembayaran::factory()->menunggu()->create([
        'pos_user_id' => PosUser::factory()->create()->id,
    ]);

    $this->postJson("/api/v1/pembayaran/{$pembayaran->id}/tandai-gagal")
        ->assertOk()
        ->assertJsonPath('status', StatusPembayaran::Gagal->value);

    expect(LogAktivitas::query()->where('aksi', JenisAksi::PembayaranGagal->value)->exists())->toBeTrue();
});

it('menolak menggagalkan invoice yang sudah lunas', function (): void {
    // Menariknya kembali berarti masa aktif langganan juga harus dicabut;
    // selama alur itu belum ada, jalan ini ditutup di server, bukan hanya di UI.
    $pembayaran = Pembayaran::factory()->create(['pos_user_id' => PosUser::factory()->create()->id]);

    $this->postJson("/api/v1/pembayaran/{$pembayaran->id}/tandai-gagal")->assertStatus(422);

    expect($pembayaran->refresh()->status)->toBe(StatusPembayaran::Lunas);
});
