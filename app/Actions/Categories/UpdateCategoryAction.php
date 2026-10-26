<?php

namespace App\Actions\Categories;

use App\Models\Category;

class UpdateCategoryAction
{
    /**
     * Perbarui nama kategori.
     *
     * @param  array{name: string}  $data
     */
    public function execute(Category $category, array $data): Category
    {
        $category->update([
            'name' => $data['name'],
        ]);

        return $category;
    }
}
