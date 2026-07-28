<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\IkonKategori;
use App\Models\Kategori;
use App\Models\PosUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Kategori> */
class KategoriFactory extends Factory
{
    protected $model = Kategori::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'pos_user_id' => PosUser::factory(),
            'nama' => fake()->unique()->randomElement([
                'Kopi', 'Non-Kopi', 'Makanan', 'Camilan', 'Roti', 'Minuman Dingin',
            ]).' '.fake()->unique()->numberBetween(1, 9999),
            'ikon' => fake()->randomElement(IkonKategori::cases()),
            'urutan' => 0,
        ];
    }
}
