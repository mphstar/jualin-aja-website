<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\JenisAksi;
use App\Enums\TargetAksi;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LogAktivitas>
 */
class LogAktivitasFactory extends Factory
{
    protected $model = LogAktivitas::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $aksi = fake()->randomElement(JenisAksi::cases());

        return [
            'waktu' => fake()->dateTimeBetween('-90 days', 'now'),
            'aktor_id' => User::factory(),
            'aktor_nama' => fake()->name(),
            'aksi' => $aksi,
            'target_tipe' => TargetAksi::Sistem,
            'target_id' => null,
            'target_label' => null,
            'deskripsi' => $aksi->label().'.',
        ];
    }
}
