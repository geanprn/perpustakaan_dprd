<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed kategori buku contoh.
     */
    public function run(): void
    {
        $categories = [
            'Fiksi',
            'Nonfiksi',
            'Sejarah',
            'Agama',
            'Anak-Anak',
            'Sains & Teknologi',
        ];

        foreach ($categories as $name) {
            Category::query()->firstOrCreate(['name' => $name]);
        }
    }
}
