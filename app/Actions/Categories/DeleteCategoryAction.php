<?php

namespace App\Actions\Categories;

use App\Models\Category;

class DeleteCategoryAction
{
    /**
     * Hapus kategori.
     *
     * Mengembalikan false bila kategori masih dipakai buku (BR-08):
     * riwayat koleksi harus tetap utuh sehingga penghapusan ditolak.
     */
    public function execute(Category $category): bool
    {
        if ($category->books()->exists()) {
            return false;
        }

        $category->delete();

        return true;
    }
}
