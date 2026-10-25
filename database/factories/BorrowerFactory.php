<?php

namespace Database\Factories;

use App\Models\Borrower;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Borrower>
 */
class BorrowerFactory extends Factory
{
    protected $model = Borrower::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'identity_number' => fake()->unique()->numerify('##########'),
            'institution' => fake()->randomElement([null, 'Sekretariat DPRD', 'Bagian Umum', 'Komisi A']),
            'phone' => fake()->randomElement([null, '081234567890']),
            'address' => fake()->randomElement([null, fake()->streetAddress()]),
        ];
    }
}
