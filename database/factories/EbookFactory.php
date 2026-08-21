<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\JenisKonten;
use App\Enums\KategoriEbook;
use App\Enums\KategoriPrompt;
use App\Enums\StatusEbook;
use App\Models\Ebook;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ebook>
 */
class EbookFactory extends Factory
{
    protected $model = Ebook::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $judul = Str::title(rtrim(fake()->unique()->sentence(4), '.'));

        return [
            'judul' => $judul,
            'slug' => Str::slug($judul).'-'.fake()->unique()->numerify('####'),
            'jenis' => JenisKonten::Resep,
            'kategori' => fake()->randomElement(KategoriEbook::cases()),
            'kategori_prompt' => null,
            'deskripsi' => fake()->paragraph(),
            'cover_path' => null,
            'berkas_path' => null,
            'nama_berkas' => null,
            'ukuran_berkas_bytes' => fake()->numberBetween(1_200_000, 9_800_000),
            'jumlah_halaman' => fake()->numberBetween(24, 180),
            'status' => StatusEbook::Terbit,
            'tanggal_terbit' => fake()->dateTimeBetween('-1 year', 'now'),
            'jumlah_unduhan' => 0,
        ];
    }

    public function prompt(): static
    {
        return $this->state(fn (array $attributes): array => [
            'jenis' => JenisKonten::Prompt,
            'kategori' => null,
            'kategori_prompt' => fake()->randomElement(KategoriPrompt::cases()),
        ]);
    }

    public function draf(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StatusEbook::Draf,
            'tanggal_terbit' => null,
        ]);
    }
}
