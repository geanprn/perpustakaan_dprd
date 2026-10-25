<?php

namespace Database\Factories;

use App\Models\LoanItem;
use App\Models\ReturnItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReturnItem>
 */
class ReturnItemFactory extends Factory
{
    protected $model = ReturnItem::class;

    public function definition(): array
    {
        return [
            'loan_item_id' => LoanItem::factory(),
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'return_date' => fake()->date('Y-m-d', 'today'),
            'quantity' => fake()->numberBetween(1, 3),
        ];
    }
}
