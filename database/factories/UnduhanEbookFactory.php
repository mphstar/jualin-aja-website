<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Ebook;
use App\Models\PosUser;
use App\Models\UnduhanEbook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnduhanEbook>
 */
class UnduhanEbookFactory extends Factory
{
    protected $model = UnduhanEbook::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'ebook_id' => Ebook::factory(),
            'pos_user_id' => PosUser::factory(),
            'tanggal' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
