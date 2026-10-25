<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Fiksi', 'Nonfiksi', 'Sejarah', 'Agama', 'Anak-Anak',
                'Sains & Teknologi', 'Bahasa', 'Seni & Budaya', 'Geografi',
                'Psikologi', 'Ekonomi', 'Hukum',
            ]),
        ];
    }
}
