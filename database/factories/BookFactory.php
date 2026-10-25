<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(1, 10);
        $borrowed = fake()->numberBetween(0, $total);

        return [
            'category_id' => Category::inRandomOrder()->first()?->id ?? Category::factory(),
            'title' => fake()->sentence(4),
            'author' => fake()->name(),
            'publisher' => fake()->randomElement([null, null, fake()->company()]),
            'publication_year' => fake()->numberBetween(1950, 2025),
            'shelf_location' => fake()->randomElement([null, 'A1', 'B3', 'C5', 'D2']),
            'total_copies' => $total,
            'available_copies' => $total - $borrowed,
        ];
    }
}
