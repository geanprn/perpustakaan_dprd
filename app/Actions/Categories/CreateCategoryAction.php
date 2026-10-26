<?php

namespace App\Actions\Categories;

use App\Models\Category;

class CreateCategoryAction
{
    /**
     * Buat kategori baru.
     *
     * @param  array{name: string}  $data
     */
    public function execute(array $data): Category
    {
        return Category::query()->create([
            'name' => $data['name'],
        ]);
    }
}
