<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Categories\CreateCategoryAction;
use App\Actions\Categories\DeleteCategoryAction;
use App\Actions\Categories\UpdateCategoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Tampilkan daftar kategori dengan pencarian dan paginasi (FR-BK-07).
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $categories = Category::query()
            ->withCount('books')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(config('library.per_page'))
            ->withQueryString();

        return Inertia::render('Admin/Kategori/Index', [
            'categories' => $categories,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Simpan kategori baru.
     */
    public function store(StoreCategoryRequest $request, CreateCategoryAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    /**
     * Perbarui nama kategori.
     */
    public function update(
        UpdateCategoryRequest $request,
        Category $kategori,
        UpdateCategoryAction $action,
    ): RedirectResponse {
        $action->execute($kategori, $request->validated());

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Hapus kategori.
     *
     * Penghapusan ditolak bila kategori masih dipakai buku (BR-08).
     */
    public function destroy(Category $kategori, DeleteCategoryAction $action): RedirectResponse
    {
        $terhapus = $action->execute($kategori);

        if (! $terhapus) {
            return back()->with(
                'error',
                'Kategori tidak dapat dihapus karena masih dipakai oleh buku.',
            );
        }

        return back()->with('success', 'Kategori berhasil dihapus.');
    }
}
