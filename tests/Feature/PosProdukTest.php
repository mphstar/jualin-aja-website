<?php

declare(strict_types=1);

use App\Models\Kategori;
use App\Models\PosUser;
use App\Models\Produk;

beforeEach(function (): void {
    $this->tokoGratis = PosUser::factory()->bisaMasuk()->create();
    $this->kategoriGratis = Kategori::factory()->create(['pos_user_id' => $this->tokoGratis->id]);

    $this->tokoBerlangganan = PosUser::factory()->bisaMasuk()->berlangganan()->create();
    $this->kategoriBerlangganan = Kategori::factory()->create(['pos_user_id' => $this->tokoBerlangganan->id]);
});

it('membatasi tambah produk maksimal 5 items pada versi gratis', function (): void {
    // Buat 5 produk pertama untuk toko gratis
    Produk::factory()->count(5)->create([
        'pos_user_id' => $this->tokoGratis->id,
        'kategori_id' => $this->kategoriGratis->id,
    ]);

    // Tambah produk ke-6 harus ditolak
    $this->actingAs($this->tokoGratis, 'pos')
        ->postJson('/api/mobile/v1/produk', [
            'nama' => 'Produk Ke-6',
            'kategoriId' => $this->kategoriGratis->id,
            'hargaJual' => 10000,
            'satuan' => 'pcs',
            'lacakStok' => false,
            'stok' => 0,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Batas maksimal produk untuk versi Gratis telah tercapai (maksimal 5 produk). Tingkatkan paket untuk menambah produk tanpa batas.');
});

it('dapat menghapus produk milik toko sendiri', function (): void {
    $produk = Produk::factory()->create([
        'pos_user_id' => $this->tokoGratis->id,
        'kategori_id' => $this->kategoriGratis->id,
        'nama' => 'Produk Dihapus',
    ]);

    $this->actingAs($this->tokoGratis, 'pos')
        ->deleteJson("/api/mobile/v1/produk/{$produk->id}")
        ->assertNoContent();

    expect(Produk::whereKey($produk->id)->exists())->toBeFalse();
});

it('menolak menghapus produk milik toko lain', function (): void {
    $produkLain = Produk::factory()->create([
        'pos_user_id' => $this->tokoBerlangganan->id,
        'kategori_id' => $this->kategoriBerlangganan->id,
    ]);

    $this->actingAs($this->tokoGratis, 'pos')
        ->deleteJson("/api/mobile/v1/produk/{$produkLain->id}")
        ->assertNotFound();

    expect(Produk::whereKey($produkLain->id)->exists())->toBeTrue();
});
