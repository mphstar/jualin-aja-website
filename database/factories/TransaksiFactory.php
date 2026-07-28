<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MetodeBayarPos;
use App\Enums\StatusTransaksi;
use App\Models\PosUser;
use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Transaksi> */
class TransaksiFactory extends Factory
{
    protected $model = Transaksi::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'pos_user_id' => PosUser::factory(),
            'nomor_struk' => 'STR/'.now()->year.'/'.str_pad(
                (string) fake()->unique()->numberBetween(1, 99999), 4, '0', STR_PAD_LEFT,
            ),
            'waktu' => now(),
            'metode' => MetodeBayarPos::Tunai,
            'status' => StatusTransaksi::Selesai,
            'pelanggan' => null,
            'uang_diterima' => null,
        ];
    }

    public function piutang(string $pelanggan = 'Bu Rina'): static
    {
        return $this->state(fn (array $atribut): array => [
            'status' => StatusTransaksi::Ditahan,
            'pelanggan' => $pelanggan,
            'uang_diterima' => null,
        ]);
    }
}
