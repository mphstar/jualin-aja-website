<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DurasiPaket;
use App\Enums\JenisUsaha;
use App\Enums\SumberLangganan;
use App\Models\PosUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PosUser>
 */
class PosUserFactory extends Factory
{
    protected $model = PosUser::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $nama = fake()->name();

        return [
            'nama' => $nama,
            'email' => fake()->unique()->safeEmail(),
            'telepon' => '08'.fake()->numerify('##########'),
            'avatar_url' => null,
            'nama_toko' => fake()->randomElement(['Kopi', 'Warung', 'Dapur', 'Kedai', 'Toko'])
                .' '.fake()->randomElement(['Senja', 'Bahagia', 'Nusantara', 'Barokah', 'Mekar']),
            'jenis_usaha' => fake()->randomElement(JenisUsaha::cases()),
            'kota' => fake()->randomElement([
                'Jakarta Selatan', 'Bandung', 'Surabaya', 'Yogyakarta', 'Semarang',
                'Medan', 'Makassar', 'Denpasar', 'Malang', 'Bogor',
            ]),
            'tanggal_daftar' => fake()->dateTimeBetween('-2 years', '-1 month'),
            'ditangguhkan' => false,
            'alasan_penangguhan' => null,
            'password' => null,
        ];
    }

    /** Toko yang bisa masuk dari aplikasi POS. */
    public function bisaMasuk(string $kataSandi = 'rahasia123'): static
    {
        return $this->state(fn (array $attributes): array => [
            'password' => $kataSandi,
            'alamat' => 'Jl. Contoh No. 1',
        ]);
    }

    /** Langganan aktif tanpa membuat riwayat siklus — cukup kolom ringkasannya. */
    public function berlangganan(int $sisaHari = 30): static
    {
        return $this->state(fn (array $attributes): array => [
            'langganan_berakhir_pada' => now()->addDays($sisaHari),
            'langganan_durasi' => DurasiPaket::Bulanan,
            'langganan_sumber' => SumberLangganan::Pembelian,
        ]);
    }

    public function kedaluwarsa(): static
    {
        return $this->state(fn (array $attributes): array => [
            'langganan_berakhir_pada' => now()->subDays(3),
            'langganan_durasi' => DurasiPaket::Bulanan,
            'langganan_sumber' => SumberLangganan::Pembelian,
        ]);
    }

    public function ditangguhkan(string $alasan = 'Melanggar ketentuan layanan.'): static
    {
        return $this->state(fn (array $attributes): array => [
            'ditangguhkan' => true,
            'alasan_penangguhan' => $alasan,
        ]);
    }
}
