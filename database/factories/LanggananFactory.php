<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DurasiPaket;
use App\Enums\SumberLangganan;
use App\Models\Langganan;
use App\Models\PosUser;
use App\Support\KondisiLangganan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Langganan>
 */
class LanggananFactory extends Factory
{
    protected $model = Langganan::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $durasi = fake()->randomElement(DurasiPaket::berbayar());
        $mulai = CarbonImmutable::instance(fake()->dateTimeBetween('-1 year', 'now'));

        return [
            'pos_user_id' => PosUser::factory(),
            'durasi' => $durasi,
            'sumber' => SumberLangganan::Pembelian,
            'tanggal_mulai' => $mulai,
            'tanggal_berakhir' => $mulai->addMonths($durasi->bulan()),
            'dibuat_oleh' => null,
            'catatan' => null,
        ];
    }

    /** Siklus uji coba 14 hari yang otomatis dibuat saat pendaftaran. */
    public function trial(): static
    {
        return $this->state(function (array $attributes): array {
            $mulai = CarbonImmutable::instance(fake()->dateTimeBetween('-1 year', 'now'));

            return [
                'durasi' => DurasiPaket::Trial,
                'sumber' => SumberLangganan::Trial,
                'tanggal_mulai' => $mulai,
                'tanggal_berakhir' => $mulai->addDays(KondisiLangganan::LAMA_TRIAL_HARI),
            ];
        });
    }

    /** Berakhir tepat `$sisaHari` hari dari sekarang — untuk menguji ambang status. */
    public function berakhirDalam(int $sisaHari): static
    {
        return $this->state(fn (array $attributes): array => [
            'tanggal_berakhir' => CarbonImmutable::now()->startOfDay()->addDays($sisaHari)->addHours(12),
        ]);
    }
}
