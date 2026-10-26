<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use App\Support\CatalogPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Katalog publik: daftar buku, pencarian, dan filter kategori
 * (FR-PUB-02, FR-PUB-03, FR-PUB-04, FR-PUB-07).
 */
class CatalogController extends Controller
{
    /**
     * Kolom buku yang aman dikirim ke halaman publik.
     *
     * @var array<int, string>
     */
    private const BOOK_COLUMNS = [
        'id',
        'category_id',
        'title',
        'author',
        'shelf_location',
        'total_copies',
        'available_copies',
    ];

    /**
     * Tampilkan daftar buku dengan pencarian, filter kategori, dan paginasi.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $categoryId = $this->resolveCategoryId($request);

        $books = Book::query()
            ->select(self::BOOK_COLUMNS)
            ->with('category:id,name')
            ->when($search !== '', fn (Builder $query) => $this->applySearch($query, $search))
            ->when($categoryId !== null, fn (Builder $query) => $query->where('category_id', $categoryId))
            ->orderBy('title')
            ->paginate(config('library.per_page'))
            ->withQueryString()
            ->through(fn (Book $book): array => CatalogPresenter::forList($book));

        return Inertia::render('Public/Katalog/Index', [
            'books' => $books,
            'categories' => $this->categoryOptions(),
            'filters' => [
                'search' => $search,
                'category' => $categoryId,
            ],
            'showExactStock' => CatalogPresenter::showExactStock(),
        ]);
    }

    /**
     * Tampilkan detail satu buku (FR-PUB-05).
     */
    public function show(Book $book): Response
    {
        $book->load('category:id,name');

        return Inertia::render('Public/Katalog/Show', [
            'book' => CatalogPresenter::forDetail($book),
            'showExactStock' => CatalogPresenter::showExactStock(),
        ]);
    }

    /**
     * Terapkan pencarian judul, pengarang, atau nama kategori (FR-PUB-03).
     *
     * @param  Builder<Book>  $query
     */
    private function applySearch(Builder $query, string $search): void
    {
        $pattern = "%{$search}%";

        $query->where(function (Builder $subQuery) use ($pattern): void {
            $subQuery
                ->where('title', 'like', $pattern)
                ->orWhere('author', 'like', $pattern)
                ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('name', 'like', $pattern));
        });
    }

    /**
     * Ambil kategori yang valid dari query string; null bila kosong/tidak ada.
     */
    private function resolveCategoryId(Request $request): ?int
    {
        $raw = $request->query('category');

        if ($raw === null || $raw === '') {
            return null;
        }

        $categoryId = filter_var($raw, FILTER_VALIDATE_INT);

        if ($categoryId === false || $categoryId <= 0) {
            return null;
        }

        return Category::query()->whereKey($categoryId)->exists() ? $categoryId : null;
    }

    /**
     * Daftar kategori untuk kontrol filter (FR-PUB-04).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function categoryOptions(): Collection
    {
        return Category::query()
            ->withCount('books')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category): array => [
                'id' => (int) $category->getKey(),
                'name' => $category->name,
                'books_count' => (int) $category->books_count,
            ]);
    }
}
