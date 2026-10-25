<?php

namespace Database\Factories;

use App\Enums\LoanItemStatus;
use App\Models\Book;
use App\Models\Loan;
use App\Models\LoanItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanItem>
 */
class LoanItemFactory extends Factory
{
    protected $model = LoanItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);

        return [
            'loan_id' => Loan::factory(),
            'book_id' => Book::factory(),
            'quantity' => $quantity,
            'returned_quantity' => 0,
            'status' => LoanItemStatus::dipinjam->value,
        ];
    }
}
