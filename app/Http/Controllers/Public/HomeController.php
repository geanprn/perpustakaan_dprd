<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use App\Support\CatalogPresenter;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman beranda publik (FR-PUB-01).
 */
class HomeController extends Controller
{
    /**
     * Jumlah buku terbaru yang ditampilkan di beranda.
     */
    private const LATEST_BOOK_LIMIT = 6;

    /**
     * Tampilkan pengantar perpustakaan, kotak pencarian, dan tautan katalog.
     */
    public function index(): Response
    {
        $latestBooks = Book::query()
            ->select([
                'id',
                'category_id',
                'title',
                'author',
                'shelf_location',
                'total_copies',
                'available_copies',
            ])
            ->with('category:id,name')
            ->latest('id')
            ->limit(self::LATEST_BOOK_LIMIT)
            ->get()
            ->map(fn (Book $book): array => CatalogPresenter::forList($book));

        $categories = Category::query()
            ->withCount('books')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category): array => [
                'id' => (int) $category->getKey(),
                'name' => $category->name,
                'books_count' => (int) $category->books_count,
            ]);

        return Inertia::render('Public/Home', [
            'latestBooks' => $latestBooks,
            'categories' => $categories,
            'totalBooks' => Book::query()->count(),
            'showExactStock' => CatalogPresenter::showExactStock(),
        ]);
    }
}
