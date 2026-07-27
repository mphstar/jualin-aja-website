<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DurasiPaket;
use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Models\Pembayaran;
use App\Models\PosUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pembayaran>
 */
class PembayaranFactory extends Factory
{
    protected $model = Pembayaran::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $durasi = fake()->randomElement(DurasiPaket::berbayar());

        return [
            'nomor_invoice' => 'INV-'.fake()->unique()->numerify('########'),
            'pos_user_id' => PosUser::factory(),
            'langganan_id' => null,
            'nominal' => match ($durasi) {
                DurasiPaket::Bulanan => 99_000,
                DurasiPaket::Semesteran => 499_000,
                default => 899_000,
            },
            'durasi' => $durasi,
            'metode' => fake()->randomElement(MetodePembayaran::cases()),
            'status' => StatusPembayaran::Lunas,
            'tanggal' => fake()->dateTimeBetween('-1 year', 'now'),
            'catatan' => null,
        ];
    }

    public function menunggu(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StatusPembayaran::Menunggu,
        ]);
    }

    public function gagal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StatusPembayaran::Gagal,
        ]);
    }
}
