<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Kategori;
use App\Models\PosUser;
use App\Models\Produk;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Produk> */
class ProdukFactory extends Factory
{
    protected $model = Produk::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'pos_user_id' => PosUser::factory(),
            'kategori_id' => Kategori::factory(),
            'nama' => fake()->randomElement([
                'Kopi Susu Gula Aren', 'Americano', 'Matcha Latte', 'Nasi Goreng',
                'Mie Ayam', 'Roti Bakar', 'Es Teh Manis', 'Pisang Goreng',
            ]).' '.fake()->unique()->numberBetween(1, 99999),
            'harga_jual' => fake()->numberBetween(5, 60) * 1000,
            'satuan' => fake()->randomElement(['pcs', 'porsi', 'gelas', 'cup']),
            'lacak_stok' => false,
            'stok' => 0,
            'gambar_url' => null,
        ];
    }

    /** Produk yang stoknya dilacak — dipakai uji penjagaan stok. */
    public function berstok(int $stok = 10): static
    {
        return $this->state(fn (array $atribut): array => [
            'lacak_stok' => true,
            'stok' => $stok,
        ]);
    }

    public function untukToko(PosUser $toko, ?Kategori $kategori = null): static
    {
        return $this->state(fn (array $atribut): array => [
            'pos_user_id' => $toko->id,
            'kategori_id' => ($kategori ?? Kategori::factory()->create([
                'pos_user_id' => $toko->id,
            ]))->id,
        ]);
    }
}
