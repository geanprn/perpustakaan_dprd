<?php

namespace Tests\Feature\Public;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function bookPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Buku Uji',
            'author' => 'Pengarang Uji',
            'publisher' => 'Penerbit Uji',
            'publication_year' => 2020,
            'shelf_location' => 'A1',
            'total_copies' => 5,
            'available_copies' => 5,
        ], $overrides);
    }

    public function test_home_page_is_publicly_accessible_with_intro_and_latest_books(): void
    {
        $category = Category::factory()->create(['name' => 'Fiksi']);
        Book::factory()->create($this->bookPayload([
            'category_id' => $category->id,
            'title' => 'Buku Terbaru',
        ]));

        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Home')
            ->has('latestBooks', 1)
            ->where('latestBooks.0.title', 'Buku Terbaru')
            ->where('latestBooks.0.category', 'Fiksi')
            ->has('categories')
            ->where('totalBooks', 1)
            ->where('showExactStock', true)
        );
    }

    public function test_home_page_limits_latest_books_to_six(): void
    {
        Book::factory()->count(8)->create();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->has('latestBooks', 6));
    }

    public function test_catalog_lists_books_with_pagination(): void
    {
        Book::factory()->count(12)->create();

        $response = $this->get('/katalog');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Katalog/Index')
            ->has('books.data', config('library.per_page'))
            ->where('books.total', 12)
            ->has('categories')
            ->where('filters.search', '')
            ->where('filters.category', null)
        );
    }

    public function test_catalog_search_matches_title(): void
    {
        Book::factory()->create($this->bookPayload(['title' => 'Laskar Pelangi']));
        Book::factory()->create($this->bookPayload(['title' => 'Bumi Manusia']));

        $response = $this->get('/katalog?search=Laskar');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('books.data', 1)
            ->where('books.data.0.title', 'Laskar Pelangi')
            ->where('filters.search', 'Laskar')
        );
    }

    public function test_catalog_search_matches_author(): void
    {
        Book::factory()->create($this->bookPayload([
            'title' => 'Laskar Pelangi',
            'author' => 'Andrea Hirata',
        ]));
        Book::factory()->create($this->bookPayload([
            'title' => 'Bumi Manusia',
            'author' => 'Pramoedya Ananta Toer',
        ]));

        $response = $this->get('/katalog?search=Pramoedya');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('books.data', 1)
            ->where('books.data.0.author', 'Pramoedya Ananta Toer')
        );
    }

    public function test_catalog_search_matches_category_name(): void
    {
        $fiksi = Category::factory()->create(['name' => 'Fiksi']);
        $sejarah = Category::factory()->create(['name' => 'Sejarah']);

        Book::factory()->create($this->bookPayload([
            'category_id' => $fiksi->id,
            'title' => 'Buku Fiksi',
        ]));
        Book::factory()->create($this->bookPayload([
            'category_id' => $sejarah->id,
            'title' => 'Buku Sejarah',
        ]));

        $response = $this->get('/katalog?search=Sejarah');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('books.data', 1)
            ->where('books.data.0.title', 'Buku Sejarah')
            ->where('books.data.0.category', 'Sejarah')
        );
    }

    public function test_catalog_filters_by_category(): void
    {
        $fiksi = Category::factory()->create(['name' => 'Fiksi']);
        $sejarah = Category::factory()->create(['name' => 'Sejarah']);

        Book::factory()->create($this->bookPayload([
            'category_id' => $fiksi->id,
            'title' => 'Buku Fiksi',
        ]));
        Book::factory()->create($this->bookPayload([
            'category_id' => $sejarah->id,
            'title' => 'Buku Sejarah',
        ]));

        $response = $this->get("/katalog?category={$fiksi->id}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('books.data', 1)
            ->where('books.data.0.title', 'Buku Fiksi')
            ->where('filters.category', $fiksi->id)
        );
    }

    public function test_catalog_ignores_unknown_category_filter(): void
    {
        Book::factory()->create();

        $response = $this->get('/katalog?category=999999');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('books.data', 1)
            ->where('filters.category', null)
        );
    }

    public function test_catalog_keeps_query_string_on_pagination_links(): void
    {
        $category = Category::factory()->create(['name' => 'Fiksi']);
        Book::factory()->count(12)->create([
            'category_id' => $category->id,
            'title' => 'Buku Uji',
        ]);

        $response = $this->get('/katalog?search=Buku Uji');

        $response->assertOk();
        $response->assertInertia(function (Assert $page) {
            $page->has('books.data', config('library.per_page'))
                ->where('books.last_page', 2);

            $links = $page->toArray()['props']['books']['links'];
            $halamanDua = collect($links)->firstWhere('label', '2');

            $this->assertNotNull($halamanDua);
            $this->assertStringContainsString('search=Buku%20Uji', $halamanDua['url']);
            $this->assertStringContainsString('page=2', $halamanDua['url']);
        });
    }

    public function test_catalog_shows_empty_message_when_search_finds_nothing(): void
    {
        Book::factory()->create($this->bookPayload(['title' => 'Laskar Pelangi']));

        $response = $this->get('/katalog?search=tidakadayangcocok');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('books.data', 0)
            ->where('books.total', 0)
            ->where('filters.search', 'tidakadayangcocok')
        );
    }

    public function test_book_detail_shows_all_required_fields(): void
    {
        $category = Category::factory()->create(['name' => 'Fiksi']);
        $book = Book::factory()->create([
            'category_id' => $category->id,
            'title' => 'Laskar Pelangi',
            'author' => 'Andrea Hirata',
            'publisher' => 'Bentang Pustaka',
            'publication_year' => 2005,
            'shelf_location' => 'C3',
            'total_copies' => 5,
            'available_copies' => 3,
        ]);

        $response = $this->get("/katalog/{$book->id}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Katalog/Show')
            ->where('book.title', 'Laskar Pelangi')
            ->where('book.author', 'Andrea Hirata')
            ->where('book.category', 'Fiksi')
            ->where('book.publisher', 'Bentang Pustaka')
            ->where('book.publication_year', 2005)
            ->where('book.shelf_location', 'C3')
            ->where('book.total_copies', 5)
            ->where('book.available_copies', 3)
            ->where('book.is_available', true)
        );
    }

    public function test_book_detail_returns_not_found_for_unknown_book(): void
    {
        $this->get('/katalog/999999')->assertNotFound();
    }

    public function test_exact_stock_is_hidden_when_config_is_false(): void
    {
        config()->set('library.show_exact_stock', false);

        $book = Book::factory()->create([
            'total_copies' => 5,
            'available_copies' => 0,
        ]);

        $list = $this->get('/katalog');

        $list->assertOk();
        $list->assertInertia(fn (Assert $page) => $page
            ->where('books.data.0.available_copies', null)
            ->where('books.data.0.is_available', false)
            ->where('showExactStock', false)
        );

        $detail = $this->get("/katalog/{$book->id}");

        $detail->assertOk();
        $detail->assertInertia(fn (Assert $page) => $page
            ->where('book.available_copies', null)
            ->where('book.is_available', false)
        );
    }

    public function test_public_payload_does_not_expose_internal_columns(): void
    {
        $book = Book::factory()->create();

        $response = $this->get("/katalog/{$book->id}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->missing('book.category_id')
            ->missing('book.created_at')
            ->missing('book.updated_at')
        );

        $list = $this->get('/katalog');

        $list->assertInertia(fn (Assert $page) => $page
            ->missing('books.data.0.category_id')
            ->missing('books.data.0.created_at')
        );
    }

    public function test_catalog_page_contains_no_admin_or_loan_routes(): void
    {
        Book::factory()->create();

        $response = $this->get('/katalog');

        $response->assertOk();
        $response->assertDontSee('/admin/buku', false);
        $response->assertDontSee('/admin/peminjaman', false);
    }

    public function test_guest_cannot_reach_admin_routes_from_public_pages(): void
    {
        $book = Book::factory()->create();

        $this->get('/')->assertOk();
        $this->get('/katalog')->assertOk();
        $this->get("/katalog/{$book->id}")->assertOk();

        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/admin/buku')->assertRedirect('/login');
        $this->post('/admin/buku', ['title' => 'X'])->assertRedirect('/login');
    }
}
