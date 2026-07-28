<?php

declare(strict_types=1);

use App\Actions\Pos\SimpanTransaksiPos;
use App\Enums\MetodeBayarPos;
use App\Enums\StatusTransaksi;
use App\Models\Kategori;
use App\Models\PosUser;
use App\Models\Produk;
use App\Models\Transaksi;

/**
 * Toko yang sudah masuk lewat guard `pos`, langganan masih berjalan.
 *
 * @return array{0: PosUser, 1: Kategori}
 */
function toko(array $atribut = []): array
{
    $toko = PosUser::factory()->bisaMasuk()->berlangganan()->create($atribut);
    $kategori = Kategori::factory()->create(['pos_user_id' => $toko->id]);

    test()->actingAs($toko, 'pos');

    return [$toko, $kategori];
}

// ---------------------------------------------------------------------------
// Stok tidak boleh minus — inti seluruh berkas ini
// ---------------------------------------------------------------------------

it('mengurangi stok saat transaksi tersimpan', function (): void {
    [$toko, $kategori] = toko();
    $produk = Produk::factory()->berstok(10)->untukToko($toko, $kategori)->create();

    $this->postJson(route('api.mobile.transaksi.store'), [
        'item' => [['produkId' => $produk->id, 'jumlah' => 3]],
        'metode' => 'TUNAI',
        'status' => 'SELESAI',
        'uangDiterima' => $produk->harga_jual * 3,
        // 201, bukan 200: Laravel menandai resource dari model yang baru saja
        // dibuat sebagai Created. Diuji apa adanya supaya perubahan status di masa
        // depan tidak lewat begitu saja.
    ])->assertCreated()->assertJsonPath('total', $produk->harga_jual * 3);

    expect($produk->refresh()->stok)->toBe(7);
});

it('menolak transaksi yang melebihi stok dan tidak mengubah apa pun', function (): void {
    [$toko, $kategori] = toko();
    $produk = Produk::factory()->berstok(2)->untukToko($toko, $kategori)->create();

    $respons = $this->postJson(route('api.mobile.transaksi.store'), [
        'item' => [['produkId' => $produk->id, 'jumlah' => 3]],
        'metode' => 'TUNAI',
        'status' => 'SELESAI',
        'uangDiterima' => 999_000,
    ]);

    $respons->assertStatus(422);
    expect($respons->json('message'))->toContain('tidak cukup');

    // Yang paling penting: transaksinya ikut dibatalkan seluruhnya, bukan
    // tersimpan setengah dengan stok yang sudah terlanjur berkurang.
    expect($produk->refresh()->stok)->toBe(2)
        ->and(Transaksi::query()->count())->toBe(0);
});

it('menolak produk yang stoknya sudah habis', function (): void {
    [$toko, $kategori] = toko();
    $produk = Produk::factory()->berstok(0)->untukToko($toko, $kategori)->create();

    $this->postJson(route('api.mobile.transaksi.store'), [
        'item' => [['produkId' => $produk->id, 'jumlah' => 1]],
        'metode' => 'QRIS',
        'status' => 'SELESAI',
    ])->assertStatus(422);

    expect($produk->refresh()->stok)->toBe(0);
});

it('membiarkan produk tanpa pelacakan stok dijual berapa pun', function (): void {
    [$toko, $kategori] = toko();
    $produk = Produk::factory()->untukToko($toko, $kategori)->create(['lacak_stok' => false]);

    $this->postJson(route('api.mobile.transaksi.store'), [
        'item' => [['produkId' => $produk->id, 'jumlah' => 500]],
        'metode' => 'QRIS',
        'status' => 'SELESAI',
    ])->assertCreated();

    expect($produk->refresh()->stok)->toBe(0);
});

it('menggagalkan seluruh keranjang kalau satu barang saja tidak cukup', function (): void {
    [$toko, $kategori] = toko();
    $cukup = Produk::factory()->berstok(10)->untukToko($toko, $kategori)->create();
    $kurang = Produk::factory()->berstok(1)->untukToko($toko, $kategori)->create();

    $this->postJson(route('api.mobile.transaksi.store'), [
        'item' => [
            ['produkId' => $cukup->id, 'jumlah' => 2],
            ['produkId' => $kurang->id, 'jumlah' => 5],
        ],
        'metode' => 'QRIS',
        'status' => 'SELESAI',
    ])->assertStatus(422);

    // Barang yang stoknya cukup TIDAK boleh ikut berkurang — separuh transaksi
    // yang tersimpan adalah bentuk kerusakan paling sulit ditemukan, karena
    // tidak ada satu pun struk yang menunjukkannya.
    expect($cukup->refresh()->stok)->toBe(10)
        ->and($kurang->refresh()->stok)->toBe(1);
});

it('menjumlahkan baris kembar sebelum memeriksa stok', function (): void {
    [$toko, $kategori] = toko();
    $produk = Produk::factory()->berstok(3)->untukToko($toko, $kategori)->create();

    // Dua baris @2 = 4, sementara stok cuma 3. Kalau dijaga per baris, keduanya
    // lolos dan stok berakhir negatif.
    $this->postJson(route('api.mobile.transaksi.store'), [
        'item' => [
            ['produkId' => $produk->id, 'jumlah' => 2],
            ['produkId' => $produk->id, 'jumlah' => 2],
        ],
        'metode' => 'QRIS',
        'status' => 'SELESAI',
    ])->assertStatus(422);

    expect($produk->refresh()->stok)->toBe(3);
});

// ---------------------------------------------------------------------------
// Uang & piutang
// ---------------------------------------------------------------------------

it('menolak tunai yang belum menutup total', function (): void {
    [$toko, $kategori] = toko();
    $produk = Produk::factory()->untukToko($toko, $kategori)->create(['harga_jual' => 20_000]);

    $this->postJson(route('api.mobile.transaksi.store'), [
        'item' => [['produkId' => $produk->id, 'jumlah' => 1]],
        'metode' => 'TUNAI',
        'status' => 'SELESAI',
        'uangDiterima' => 15_000,
    ])->assertStatus(422);
});

it('mewajibkan nama pembeli untuk bayar nanti', function (): void {
    [$toko, $kategori] = toko();
    $produk = Produk::factory()->untukToko($toko, $kategori)->create();

    $this->postJson(route('api.mobile.transaksi.store'), [
        'item' => [['produkId' => $produk->id, 'jumlah' => 1]],
        'metode' => 'TUNAI',
        'status' => 'DITAHAN',
    ])->assertStatus(422)->assertJsonValidationErrors('pelanggan');
});

it('mengurangi stok untuk piutang juga karena barangnya sudah keluar rak', function (): void {
    [$toko, $kategori] = toko();
    $produk = Produk::factory()->berstok(10)->untukToko($toko, $kategori)->create();

    $this->postJson(route('api.mobile.transaksi.store'), [
        'item' => [['produkId' => $produk->id, 'jumlah' => 4]],
        'metode' => 'TUNAI',
        'status' => 'DITAHAN',
        'pelanggan' => 'Bu Rina',
    ])->assertCreated();

    expect($produk->refresh()->stok)->toBe(6);
});

it('mengembalikan stok saat isi piutang dikurangi', function (): void {
    [$toko, $kategori] = toko();
    $produk = Produk::factory()->berstok(10)->untukToko($toko, $kategori)->create();

    $transaksi = app(SimpanTransaksiPos::class)(
        toko: $toko,
        item: [$produk->id => 4],
        metode: MetodeBayarPos::Tunai,
        status: StatusTransaksi::Ditahan,
        pelanggan: 'Bu Rina',
    );

    expect($produk->refresh()->stok)->toBe(6);

    $this->putJson(route('api.mobile.transaksi.isi', $transaksi), [
        'item' => [['produkId' => $produk->id, 'jumlah' => 1]],
    ])->assertOk()->assertJsonPath('jumlahItem', 1);

    expect($produk->refresh()->stok)->toBe(9);
});

it('menolak menambah isi piutang melebihi sisa stok', function (): void {
    [$toko, $kategori] = toko();
    $produk = Produk::factory()->berstok(5)->untukToko($toko, $kategori)->create();

    $transaksi = app(SimpanTransaksiPos::class)(
        toko: $toko,
        item: [$produk->id => 2],
        metode: MetodeBayarPos::Tunai,
        status: StatusTransaksi::Ditahan,
        pelanggan: 'Bu Rina',
    );

    // Sisa rak 3, di struk sudah 2 → paling banyak jadi 5.
    $this->putJson(route('api.mobile.transaksi.isi', $transaksi), [
        'item' => [['produkId' => $produk->id, 'jumlah' => 9]],
    ])->assertStatus(422);

    expect($produk->refresh()->stok)->toBe(3);
});

it('mempertahankan harga lama saat isi piutang diubah', function (): void {
    [$toko, $kategori] = toko();
    $produk = Produk::factory()->untukToko($toko, $kategori)->create(['harga_jual' => 10_000]);

    $transaksi = app(SimpanTransaksiPos::class)(
        toko: $toko,
        item: [$produk->id => 1],
        metode: MetodeBayarPos::Tunai,
        status: StatusTransaksi::Ditahan,
        pelanggan: 'Bu Rina',
    );

    $produk->update(['harga_jual' => 25_000]);

    // Pembeli mengambil barangnya pada harga hari itu; menagih selisihnya
    // sekarang adalah menagih sesuatu yang tidak pernah disepakati.
    $this->putJson(route('api.mobile.transaksi.isi', $transaksi), [
        'item' => [['produkId' => $produk->id, 'jumlah' => 2]],
    ])->assertOk()->assertJsonPath('total', 20_000);
});

it('menolak mengubah isi transaksi yang sudah selesai', function (): void {
    [$toko, $kategori] = toko();
    $produk = Produk::factory()->untukToko($toko, $kategori)->create();

    $transaksi = app(SimpanTransaksiPos::class)(
        toko: $toko,
        item: [$produk->id => 1],
        metode: MetodeBayarPos::Qris,
        status: StatusTransaksi::Selesai,
    );

    $this->putJson(route('api.mobile.transaksi.isi', $transaksi), [
        'item' => [['produkId' => $produk->id, 'jumlah' => 2]],
    ])->assertStatus(422);
});

it('melunasi piutang tanpa menggeser tanggal omzetnya', function (): void {
    [$toko, $kategori] = toko();
    $produk = Produk::factory()->untukToko($toko, $kategori)->create(['harga_jual' => 10_000]);

    $transaksi = app(SimpanTransaksiPos::class)(
        toko: $toko,
        item: [$produk->id => 1],
        metode: MetodeBayarPos::Tunai,
        status: StatusTransaksi::Ditahan,
        pelanggan: 'Bu Rina',
    );
    $waktuAsli = $transaksi->waktu;

    $this->postJson(route('api.mobile.transaksi.lunasi', $transaksi), [
        'metode' => 'TUNAI',
        'uangDiterima' => 20_000,
    ])->assertOk()
        ->assertJsonPath('status', 'SELESAI')
        ->assertJsonPath('kembalian', 10_000);

    expect($transaksi->refresh()->waktu->equalTo($waktuAsli))->toBeTrue();
});

it('menolak melunasi piutang dua kali', function (): void {
    [$toko, $kategori] = toko();
    $produk = Produk::factory()->untukToko($toko, $kategori)->create(['harga_jual' => 10_000]);

    $transaksi = app(SimpanTransaksiPos::class)(
        toko: $toko,
        item: [$produk->id => 1],
        metode: MetodeBayarPos::Tunai,
        status: StatusTransaksi::Ditahan,
        pelanggan: 'Bu Rina',
    );

    $this->postJson(route('api.mobile.transaksi.lunasi', $transaksi), ['metode' => 'QRIS'])
        ->assertOk();

    $this->postJson(route('api.mobile.transaksi.lunasi', $transaksi), ['metode' => 'QRIS'])
        ->assertStatus(422);
});

// ---------------------------------------------------------------------------
// Nomor struk
// ---------------------------------------------------------------------------

it('memberi nomor struk berurut per toko', function (): void {
    [$toko, $kategori] = toko();
    $produk = Produk::factory()->untukToko($toko, $kategori)->create();

    $nomor = [];

    for ($i = 0; $i < 3; $i++) {
        $nomor[] = $this->postJson(route('api.mobile.transaksi.store'), [
            'item' => [['produkId' => $produk->id, 'jumlah' => 1]],
            'metode' => 'QRIS',
            'status' => 'SELESAI',
        ])->json('nomorStruk');
    }

    $tahun = now()->year;

    expect($nomor)->toBe([
        "STR/{$tahun}/0001",
        "STR/{$tahun}/0002",
        "STR/{$tahun}/0003",
    ]);
});

it('tidak memakai ulang nomor struk transaksi yang dihapus', function (): void {
    [$toko, $kategori] = toko();
    $produk = Produk::factory()->untukToko($toko, $kategori)->create();

    app(SimpanTransaksiPos::class)(
        toko: $toko,
        item: [$produk->id => 1],
        metode: MetodeBayarPos::Qris,
        status: StatusTransaksi::Selesai,
    )->delete();

    $kedua = app(SimpanTransaksiPos::class)(
        toko: $toko->refresh(),
        item: [$produk->id => 1],
        metode: MetodeBayarPos::Qris,
        status: StatusTransaksi::Selesai,
    );

    expect($kedua->nomor_struk)->toBe('STR/'.now()->year.'/0002');
});
