<?php

namespace Tests\Feature\Admin;

use App\Models\Book;
use App\Models\Borrower;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BookManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Buku Baru',
            'author' => 'Pengarang Baru',
            'category_id' => Category::factory()->create()->id,
            'publisher' => 'Penerbit',
            'publication_year' => 2020,
            'shelf_location' => 'A1',
            'total_copies' => 5,
        ], $overrides);
    }

    public function test_guest_cannot_access_book_routes(): void
    {
        $book = Book::factory()->create();

        $this->get('/admin/buku')->assertRedirect('/login');
        $this->post('/admin/buku', $this->validPayload())->assertRedirect('/login');
        $this->put("/admin/buku/{$book->id}", ['title' => 'X'])->assertRedirect('/login');
        $this->delete("/admin/buku/{$book->id}")->assertRedirect('/login');
    }

    public function test_index_lists_books_with_category(): void
    {
        $category = Category::factory()->create(['name' => 'Fiksi']);
        Book::factory()->create([
            'category_id' => $category->id,
            'title' => 'Laskar Pelangi',
            'author' => 'Andrea Hirata',
            'total_copies' => 5,
            'available_copies' => 3,
        ]);

        $response = $this->actingAs($this->admin())->get('/admin/buku');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Buku/Index')
            ->has('books.data', 1)
            ->where('books.data.0.title', 'Laskar Pelangi')
            ->where('books.data.0.category.name', 'Fiksi')
            ->where('books.data.0.total_copies', 5)
            ->where('books.data.0.available_copies', 3)
            ->has('categories')
            ->where('filters.search', '')
        );
    }

    public function test_index_search_matches_title(): void
    {
        Book::factory()->create(['title' => 'Laskar Pelangi', 'author' => 'Andrea']);
        Book::factory()->create(['title' => 'Bumi Manusia', 'author' => 'Pramoedya']);

        $response = $this->actingAs($this->admin())->get('/admin/buku?search=Laskar');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('books.data', 1)
            ->where('books.data.0.title', 'Laskar Pelangi')
        );
    }

    public function test_index_search_matches_author(): void
    {
        Book::factory()->create(['title' => 'Laskar Pelangi', 'author' => 'Andrea Hirata']);
        Book::factory()->create(['title' => 'Bumi Manusia', 'author' => 'Pramoedya']);

        $response = $this->actingAs($this->admin())->get('/admin/buku?search=Pramoedya');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('books.data', 1)
            ->where('books.data.0.author', 'Pramoedya')
        );
    }

    public function test_index_paginates_and_keeps_search_query_string(): void
    {
        Book::factory()->count(12)->create(['title' => 'Buku Uji']);

        $response = $this->actingAs($this->admin())->get('/admin/buku?search=Buku Uji');

        $response->assertOk();
        $response->assertInertia(function (Assert $page) {
            $page->has('books.data', config('library.per_page'))
                ->where('books.total', 12)
                ->where('books.last_page', 2);

            $links = $page->toArray()['props']['books']['links'];
            $halamanDua = collect($links)->firstWhere('label', '2');

            $this->assertNotNull($halamanDua);
            $this->assertStringContainsString('search=Buku%20Uji', $halamanDua['url']);
            $this->assertStringContainsString('page=2', $halamanDua['url']);
        });
    }

    public function test_store_creates_book_with_available_copies_equal_to_total(): void
    {
        $payload = $this->validPayload(['total_copies' => 7]);

        $response = $this->actingAs($this->admin())
            ->from('/admin/buku')
            ->post('/admin/buku', $payload);

        $response->assertRedirect('/admin/buku');
        $response->assertSessionHas('success', 'Buku berhasil ditambahkan.');

        $book = Book::query()->where('title', 'Buku Baru')->firstOrFail();

        $this->assertSame(7, $book->total_copies);
        $this->assertSame(7, $book->available_copies);
    }

    public function test_store_allows_nullable_fields_empty(): void
    {
        $payload = $this->validPayload([
            'publisher' => '',
            'publication_year' => '',
            'shelf_location' => '',
        ]);

        $response = $this->actingAs($this->admin())
            ->from('/admin/buku')
            ->post('/admin/buku', $payload);

        $response->assertSessionHasNoErrors();

        $book = Book::query()->where('title', 'Buku Baru')->firstOrFail();

        $this->assertNull($book->publisher);
        $this->assertNull($book->publication_year);
        $this->assertNull($book->shelf_location);
    }

    public function test_store_validation_messages_are_indonesian_per_column(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin())
            ->from('/admin/buku')
            ->post('/admin/buku', [
                'title' => '',
                'author' => '',
                'category_id' => $category->id,
                'publication_year' => 999,
                'total_copies' => 0,
            ]);

        $response->assertSessionHasErrors([
            'title' => 'Judul buku wajib diisi.',
            'author' => 'Pengarang wajib diisi.',
            'publication_year' => 'Tahun terbit minimal tahun 1000.',
            'total_copies' => 'Jumlah eksemplar minimal 1.',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_store_rejects_unknown_category(): void
    {
        $response = $this->actingAs($this->admin())
            ->from('/admin/buku')
            ->post('/admin/buku', $this->validPayload(['category_id' => 9999]));

        $response->assertSessionHasErrors([
            'category_id' => 'Kategori yang dipilih tidak ditemukan.',
        ]);
    }

    public function test_store_rejects_future_publication_year(): void
    {
        $tahunDepan = (int) date('Y') + 1;

        $response = $this->actingAs($this->admin())
            ->from('/admin/buku')
            ->post('/admin/buku', $this->validPayload([
                'publication_year' => $tahunDepan,
            ]));

        $response->assertSessionHasErrors('publication_year');
    }

    public function test_update_changes_book_fields(): void
    {
        $categoryLama = Category::factory()->create();
        $categoryBaru = Category::factory()->create();
        $book = Book::factory()->create([
            'category_id' => $categoryLama->id,
            'title' => 'Judul Lama',
            'total_copies' => 3,
            'available_copies' => 3,
        ]);

        $response = $this->actingAs($this->admin())
            ->from('/admin/buku')
            ->put("/admin/buku/{$book->id}", [
                'title' => 'Judul Baru',
                'author' => 'Pengarang Baru',
                'category_id' => $categoryBaru->id,
                'publisher' => 'Penerbit Baru',
                'publication_year' => 2021,
                'shelf_location' => 'B2',
                'total_copies' => 3,
            ]);

        $response->assertRedirect('/admin/buku');
        $response->assertSessionHas('success', 'Buku berhasil diperbarui.');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Judul Baru',
            'category_id' => $categoryBaru->id,
            'shelf_location' => 'B2',
        ]);
    }

    public function test_update_increasing_total_copies_raises_available_copies(): void
    {
        $book = Book::factory()->create([
            'total_copies' => 3,
            'available_copies' => 2,
        ]);

        $response = $this->actingAs($this->admin())
            ->from('/admin/buku')
            ->put("/admin/buku/{$book->id}", $this->validPayload([
                'category_id' => $book->category_id,
                'total_copies' => 6,
            ]));

        $response->assertSessionHasNoErrors();

        $book->refresh();

        $this->assertSame(6, $book->total_copies);
        $this->assertSame(5, $book->available_copies);
    }

    public function test_update_decreasing_total_copies_lowers_available_copies(): void
    {
        $book = Book::factory()->create([
            'total_copies' => 5,
            'available_copies' => 3,
        ]);

        $response = $this->actingAs($this->admin())
            ->from('/admin/buku')
            ->put("/admin/buku/{$book->id}", $this->validPayload([
                'category_id' => $book->category_id,
                'total_copies' => 4,
            ]));

        $response->assertSessionHasNoErrors();

        $book->refresh();

        $this->assertSame(4, $book->total_copies);
        $this->assertSame(2, $book->available_copies);
    }

    public function test_update_rejects_total_copies_below_borrowed_count(): void
    {
        $book = Book::factory()->create([
            'total_copies' => 5,
            'available_copies' => 2,
        ]);

        $response = $this->actingAs($this->admin())
            ->from('/admin/buku')
            ->put("/admin/buku/{$book->id}", $this->validPayload([
                'category_id' => $book->category_id,
                'total_copies' => 2,
            ]));

        $response->assertSessionHasErrors([
            'total_copies' => 'Jumlah eksemplar tidak boleh kurang dari 3 eksemplar yang sedang dipinjam.',
        ]);

        $book->refresh();

        $this->assertSame(5, $book->total_copies);
        $this->assertSame(2, $book->available_copies);
    }

    public function test_update_allows_total_copies_equal_to_borrowed_count(): void
    {
        $book = Book::factory()->create([
            'total_copies' => 5,
            'available_copies' => 2,
        ]);

        $response = $this->actingAs($this->admin())
            ->from('/admin/buku')
            ->put("/admin/buku/{$book->id}", $this->validPayload([
                'category_id' => $book->category_id,
                'total_copies' => 3,
            ]));

        $response->assertSessionHasNoErrors();

        $book->refresh();

        $this->assertSame(3, $book->total_copies);
        $this->assertSame(0, $book->available_copies);
    }

    public function test_destroy_deletes_book_without_loan_history(): void
    {
        $book = Book::factory()->create();

        $response = $this->actingAs($this->admin())
            ->from('/admin/buku')
            ->delete("/admin/buku/{$book->id}");

        $response->assertRedirect('/admin/buku');
        $response->assertSessionHas('success', 'Buku berhasil dihapus.');

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_destroy_is_rejected_when_book_has_loan_history(): void
    {
        $user = $this->admin();
        $book = Book::factory()->create();
        $loan = Loan::factory()->create([
            'borrower_id' => Borrower::factory()->create()->id,
            'user_id' => $user->id,
        ]);
        LoanItem::factory()->create([
            'loan_id' => $loan->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)
            ->from('/admin/buku')
            ->delete("/admin/buku/{$book->id}");

        $response->assertRedirect('/admin/buku');
        $response->assertSessionHas(
            'error',
            'Buku tidak dapat dihapus karena memiliki riwayat peminjaman.',
        );
        $response->assertSessionMissing('success');

        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }
}
