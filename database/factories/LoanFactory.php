<?php

namespace Database\Factories;

use App\Enums\LoanStatus;
use App\Models\Borrower;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        $loanDate = fake()->date('Y-m-d', 'last month');

        return [
            'loan_number' => 'LN-'.fake()->unique()->numerify('######'),
            'borrower_id' => Borrower::factory(),
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'loan_date' => $loanDate,
            'due_date' => fake()->boolean() ? fake()->date('Y-m-d', '+30 days') : null,
            'status' => LoanStatus::berjalan->value,
            'notes' => fake()->randomElement([null, null, fake()->sentence()]),
        ];
    }
}
