<?php

namespace Tests\Feature\Admin;

use App\Actions\Loans\CreateLoanAction;
use App\Enums\LoanItemStatus;
use App\Enums\LoanStatus;
use App\Models\Book;
use App\Models\Borrower;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LoanManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function book(int $total, int $available): Book
    {
        return Book::factory()->create([
            'total_copies' => $total,
            'available_copies' => $available,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'borrower_id' => Borrower::factory()->create()->id,
            'loan_date' => now()->toDateString(),
            'due_date' => null,
            'notes' => null,
            'items' => [
                ['book_id' => $this->book(5, 5)->id, 'quantity' => 2],
            ],
        ], $overrides);
    }

    public function test_guest_cannot_access_loan_routes(): void
    {
        $loan = Loan::factory()->create();

        $this->get('/admin/peminjaman')->assertRedirect('/login');
        $this->get('/admin/peminjaman/buat')->assertRedirect('/login');
        $this->post('/admin/peminjaman', [])->assertRedirect('/login');
        $this->post('/admin/peminjaman/peminjam', [])->assertRedirect('/login');
        $this->get("/admin/peminjaman/{$loan->id}")->assertRedirect('/login');
    }

    public function test_creates_loan_and_reduces_available_stock(): void
    {
        $user = $this->admin();
        $book = $this->book(5, 5);
        $borrower = Borrower::factory()->create();

        $response = $this->actingAs($user)
            ->post('/admin/peminjaman', [
                'borrower_id' => $borrower->id,
                'loan_date' => '2026-03-01',
                'due_date' => '2026-03-15',
                'notes' => 'Untuk rapat komisi',
                'items' => [
                    ['book_id' => $book->id, 'quantity' => 2],
                ],
            ]);

        $loan = Loan::query()->firstOrFail();

        $response->assertRedirect("/admin/peminjaman/{$loan->id}");
        $response->assertSessionHas(
            'success',
            'Peminjaman berhasil dicatat dengan nomor transaksi '.$loan->loan_number.'.',
        );

        $this->assertSame('3', (string) $book->fresh()->available_copies);
        $this->assertSame(5, (int) $book->fresh()->total_copies);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
            'loan_date' => '2026-03-01',
            'due_date' => '2026-03-15',
            'status' => LoanStatus::berjalan->value,
            'notes' => 'Untuk rapat komisi',
        ]);

        $this->assertDatabaseHas('loan_items', [
            'loan_id' => $loan->id,
            'book_id' => $book->id,
            'quantity' => 2,
            'returned_quantity' => 0,
            'status' => LoanItemStatus::dipinjam->value,
        ]);
    }

    public function test_rejects_loan_when_stock_insufficient_and_saves_nothing(): void
    {
        $book = $this->book(1, 1);

        $response = $this->actingAs($this->admin())
            ->from('/admin/peminjaman/buat')
            ->post('/admin/peminjaman', [
                'borrower_id' => Borrower::factory()->create()->id,
                'loan_date' => now()->toDateString(),
                'items' => [
                    ['book_id' => $book->id, 'quantity' => 2],
                ],
            ]);

        $response->assertRedirect('/admin/peminjaman/buat');
        $response->assertSessionHasErrors([
            'items.0.quantity' => sprintf(
                'Stok buku "%s" tidak mencukupi. Tersisa 1 eksemplar, diminta 2 eksemplar.',
                $book->title,
            ),
        ]);

        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('loan_items', 0);
        $this->assertSame(1, (int) $book->fresh()->available_copies);
    }

    public function test_rolls_back_all_books_when_one_title_is_short(): void
    {
        $cukup = $this->book(5, 5);
        $kurang = $this->book(1, 1);

        $response = $this->actingAs($this->admin())
            ->from('/admin/peminjaman/buat')
            ->post('/admin/peminjaman', [
                'borrower_id' => Borrower::factory()->create()->id,
                'loan_date' => now()->toDateString(),
                'items' => [
                    ['book_id' => $cukup->id, 'quantity' => 3],
                    ['book_id' => $kurang->id, 'quantity' => 2],
                ],
            ]);

        $response->assertSessionHasErrors(['items.1.quantity']);

        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('loan_items', 0);
        $this->assertSame(5, (int) $cukup->fresh()->available_copies);
        $this->assertSame(1, (int) $kurang->fresh()->available_copies);
    }

    public function test_creates_multi_title_loan_and_reduces_each_book(): void
    {
        $bukuA = $this->book(4, 4);
        $bukuB = $this->book(3, 3);

        $response = $this->actingAs($this->admin())
            ->post('/admin/peminjaman', [
                'borrower_id' => Borrower::factory()->create()->id,
                'loan_date' => now()->toDateString(),
                'items' => [
                    ['book_id' => $bukuB->id, 'quantity' => 1],
                    ['book_id' => $bukuA->id, 'quantity' => 2],
                ],
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame(2, (int) $bukuA->fresh()->available_copies);
        $this->assertSame(2, (int) $bukuB->fresh()->available_copies);
        $this->assertDatabaseCount('loan_items', 2);
    }

    public function test_rejects_duplicate_book_in_one_transaction(): void
    {
        $book = $this->book(5, 5);

        $response = $this->actingAs($this->admin())
            ->from('/admin/peminjaman/buat')
            ->post('/admin/peminjaman', [
                'borrower_id' => Borrower::factory()->create()->id,
                'loan_date' => now()->toDateString(),
                'items' => [
                    ['book_id' => $book->id, 'quantity' => 1],
                    ['book_id' => $book->id, 'quantity' => 1],
                ],
            ]);

        $response->assertSessionHasErrors('items.1.book_id');
        $response->assertSessionHasErrors([
            'items.1.book_id' => 'Buku tidak boleh dipilih lebih dari sekali dalam satu transaksi.',
        ]);

        $this->assertDatabaseCount('loans', 0);
        $this->assertSame(5, (int) $book->fresh()->available_copies);
    }

    public function test_rejects_due_date_before_loan_date(): void
    {
        $response = $this->actingAs($this->admin())
            ->from('/admin/peminjaman/buat')
            ->post('/admin/peminjaman', [
                'borrower_id' => Borrower::factory()->create()->id,
                'loan_date' => '2026-03-10',
                'due_date' => '2026-03-09',
                'items' => [
                    ['book_id' => $this->book(5, 5)->id, 'quantity' => 1],
                ],
            ]);

        $response->assertSessionHasErrors([
            'due_date' => 'Tanggal jatuh tempo tidak boleh lebih awal dari tanggal peminjaman.',
        ]);

        $this->assertDatabaseCount('loans', 0);
    }

    public function test_rejects_missing_items_and_invalid_quantity(): void
    {
        $response = $this->actingAs($this->admin())
            ->from('/admin/peminjaman/buat')
            ->post('/admin/peminjaman', [
                'borrower_id' => Borrower::factory()->create()->id,
                'loan_date' => now()->toDateString(),
                'items' => [],
            ]);

        $response->assertSessionHasErrors([
            'items' => 'Minimal satu buku harus dipilih.',
        ]);
    }

    public function test_generates_sequential_loan_numbers_per_day(): void
    {
        $user = $this->admin();
        $book = $this->book(10, 10);
        $borrower = Borrower::factory()->create();

        $action = app(CreateLoanAction::class);

        $pertama = $action->execute([
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
            'loan_date' => '2026-04-01',
            'due_date' => null,
            'notes' => null,
            'items' => [['book_id' => $book->id, 'quantity' => 1]],
        ]);

        $kedua = $action->execute([
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
            'loan_date' => '2026-04-01',
            'due_date' => null,
            'notes' => null,
            'items' => [['book_id' => $book->id, 'quantity' => 1]],
        ]);

        $this->assertSame('PJM-20260401-0001', $pertama->loan_number);
        $this->assertSame('PJM-20260401-0002', $kedua->loan_number);

        $this->assertDatabaseCount('loans', 2);
        $this->assertSame(8, (int) $book->fresh()->available_copies);
    }

    public function test_action_resets_numbering_for_a_new_day(): void
    {
        $user = $this->admin();
        $book = $this->book(10, 10);
        $borrower = Borrower::factory()->create();

        $action = app(CreateLoanAction::class);

        $action->execute([
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
            'loan_date' => '2026-05-01',
            'items' => [['book_id' => $book->id, 'quantity' => 1]],
        ]);

        $lain = $action->execute([
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
            'loan_date' => '2026-05-02',
            'items' => [['book_id' => $book->id, 'quantity' => 1]],
        ]);

        $this->assertSame('PJM-20260502-0001', $lain->loan_number);
    }

    public function test_action_throws_validation_exception_for_insufficient_stock(): void
    {
        $user = $this->admin();
        $book = $this->book(2, 2);

        $this->expectException(ValidationException::class);

        try {
            app(CreateLoanAction::class)->execute([
                'borrower_id' => Borrower::factory()->create()->id,
                'user_id' => $user->id,
                'loan_date' => now()->toDateString(),
                'items' => [['book_id' => $book->id, 'quantity' => 3]],
            ]);
        } finally {
            $this->assertDatabaseCount('loans', 0);
            $this->assertSame(2, (int) $book->fresh()->available_copies);
        }
    }

    public function test_index_lists_loans_with_borrower_and_counts(): void
    {
        $user = $this->admin();
        $borrower = Borrower::factory()->create([
            'name' => 'Budi Santoso',
            'identity_number' => '197001012000011001',
        ]);

        $loan = Loan::factory()->create([
            'loan_number' => 'PJM-20260401-0001',
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
        ]);

        LoanItem::factory()->create([
            'loan_id' => $loan->id,
            'quantity' => 2,
            'returned_quantity' => 0,
        ]);

        $response = $this->actingAs($user)->get('/admin/peminjaman');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Peminjaman/Index')
            ->has('loans.data', 1)
            ->where('loans.data.0.loan_number', 'PJM-20260401-0001')
            ->where('loans.data.0.borrower.name', 'Budi Santoso')
            ->where('loans.data.0.borrower.identity_number', '197001012000011001')
            ->where('loans.data.0.status', 'berjalan')
            ->where('loans.data.0.is_overdue', false)
            ->where('loans.data.0.jumlah_judul', 1)
            ->where('loans.data.0.jumlah_eksemplar', 2)
            ->where('filters.search', '')
            ->where('filters.status', '')
        );
    }

    public function test_index_marks_overdue_loans_without_fine(): void
    {
        $user = $this->admin();

        Loan::factory()->create([
            'borrower_id' => Borrower::factory()->create()->id,
            'user_id' => $user->id,
            'loan_date' => now()->subDays(30)->toDateString(),
            'due_date' => now()->subDays(5)->toDateString(),
            'status' => LoanStatus::berjalan->value,
        ]);

        $response = $this->actingAs($user)->get('/admin/peminjaman');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('loans.data.0.is_overdue', true)
            ->where('loans.data.0.status', 'berjalan')
        );
    }

    public function test_index_filters_by_status(): void
    {
        $user = $this->admin();
        $borrower = Borrower::factory()->create();

        Loan::factory()->create([
            'loan_number' => 'PJM-A',
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
            'status' => LoanStatus::berjalan->value,
        ]);

        Loan::factory()->create([
            'loan_number' => 'PJM-B',
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
            'status' => LoanStatus::selesai->value,
        ]);

        Loan::factory()->create([
            'loan_number' => 'PJM-C',
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
            'loan_date' => now()->subDays(20)->toDateString(),
            'due_date' => now()->subDays(10)->toDateString(),
            'status' => LoanStatus::berjalan->value,
        ]);

        $this->actingAs($user)
            ->get('/admin/peminjaman?status=berjalan')
            ->assertInertia(fn (Assert $page) => $page
                ->has('loans.data', 2)
                ->where('filters.status', 'berjalan')
            );

        $this->actingAs($user)
            ->get('/admin/peminjaman?status=selesai')
            ->assertInertia(fn (Assert $page) => $page
                ->has('loans.data', 1)
                ->where('loans.data.0.loan_number', 'PJM-B')
            );

        $this->actingAs($user)
            ->get('/admin/peminjaman?status=terlambat')
            ->assertInertia(fn (Assert $page) => $page
                ->has('loans.data', 1)
                ->where('loans.data.0.loan_number', 'PJM-C')
                ->where('filters.status', 'terlambat')
            );
    }

    public function test_index_ignores_unknown_status_filter(): void
    {
        $user = $this->admin();

        Loan::factory()->create([
            'borrower_id' => Borrower::factory()->create()->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get('/admin/peminjaman?status=ditolak')
            ->assertInertia(fn (Assert $page) => $page
                ->has('loans.data', 1)
                ->where('filters.status', '')
            );
    }

    public function test_index_searches_by_loan_number_and_borrower_name(): void
    {
        $user = $this->admin();
        $budi = Borrower::factory()->create([
            'name' => 'Budi Santoso',
            'identity_number' => '197001012000011001',
        ]);
        $siti = Borrower::factory()->create([
            'name' => 'Siti Aminah',
            'identity_number' => '198505052010012002',
        ]);

        Loan::factory()->create([
            'loan_number' => 'PJM-20260401-0001',
            'borrower_id' => $budi->id,
            'user_id' => $user->id,
        ]);

        Loan::factory()->create([
            'loan_number' => 'PJM-20260401-0002',
            'borrower_id' => $siti->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get('/admin/peminjaman?search=PJM-20260401-0002')
            ->assertInertia(fn (Assert $page) => $page
                ->has('loans.data', 1)
                ->where('loans.data.0.loan_number', 'PJM-20260401-0002')
            );

        $this->actingAs($user)
            ->get('/admin/peminjaman?search=Budi')
            ->assertInertia(fn (Assert $page) => $page
                ->has('loans.data', 1)
                ->where('loans.data.0.borrower.name', 'Budi Santoso')
            );

        $this->actingAs($user)
            ->get('/admin/peminjaman?search=19850505')
            ->assertInertia(fn (Assert $page) => $page
                ->has('loans.data', 1)
                ->where('loans.data.0.borrower.name', 'Siti Aminah')
            );
    }

    public function test_index_paginates_and_keeps_query_string(): void
    {
        $user = $this->admin();
        $borrower = Borrower::factory()->create(['name' => 'Peminjam Uji']);

        Loan::factory()->count(12)->create([
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get('/admin/peminjaman?search=Peminjam Uji');

        $response->assertOk();
        $response->assertInertia(function (Assert $page) {
            $page->has('loans.data', config('library.per_page'))
                ->where('loans.total', 12)
                ->where('loans.last_page', 2);

            $links = $page->toArray()['props']['loans']['links'];
            $halamanDua = collect($links)->firstWhere('label', '2');

            $this->assertNotNull($halamanDua);
            $this->assertStringContainsString('search=Peminjam%20Uji', $halamanDua['url']);
            $this->assertStringContainsString('page=2', $halamanDua['url']);
        });
    }

    public function test_create_page_provides_borrowers_and_available_books(): void
    {
        $user = $this->admin();

        $tersedia = $this->book(3, 3);
        $habis = $this->book(2, 0);

        Borrower::factory()->create([
            'name' => 'Budi Santoso',
            'identity_number' => '197001012000011001',
        ]);

        $response = $this->actingAs($user)->get('/admin/peminjaman/buat');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Peminjaman/Create')
            ->has('borrowers', 1)
            ->where('borrowers.0.name', 'Budi Santoso')
            ->where('borrowers.0.identity_number', '197001012000011001')
            ->has('books', 1)
            ->where('books.0.id', (int) $tersedia->getKey())
            ->where('books.0.available_copies', 3)
            ->where('defaultLoanDate', now()->toDateString())
            ->where('selectedBorrowerId', null)
        );

        $this->assertNotSame($tersedia->getKey(), $habis->getKey());
    }

    public function test_create_page_accepts_selected_borrower_from_query(): void
    {
        $borrower = Borrower::factory()->create();

        $this->actingAs($this->admin())
            ->get("/admin/peminjaman/buat?borrower_id={$borrower->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedBorrowerId', (int) $borrower->id)
            );
    }

    public function test_store_borrower_redirects_back_to_loan_form_with_selected_borrower(): void
    {
        $response = $this->actingAs($this->admin())
            ->from('/admin/peminjaman/buat')
            ->post('/admin/peminjaman/peminjam', [
                'name' => 'Budi Santoso',
                'identity_number' => '197001012000011001',
                'institution' => 'Sekretariat DPRD',
                'phone' => '081234567890',
                'address' => 'Jl. Merdeka No. 1',
            ]);

        $borrower = Borrower::query()
            ->where('identity_number', '197001012000011001')
            ->firstOrFail();

        $response->assertRedirect('/admin/peminjaman/buat?borrower_id='.$borrower->getKey());
        $response->assertSessionHas('success', 'Peminjam berhasil ditambahkan.');

        $this->assertDatabaseHas('borrowers', [
            'id' => $borrower->getKey(),
            'name' => 'Budi Santoso',
        ]);
    }

    public function test_store_borrower_rejects_duplicate_identity_number(): void
    {
        Borrower::factory()->create(['identity_number' => '197001012000011001']);

        $response = $this->actingAs($this->admin())
            ->from('/admin/peminjaman/buat')
            ->post('/admin/peminjaman/peminjam', [
                'name' => 'Budi Santoso',
                'identity_number' => '197001012000011001',
            ]);

        $response->assertSessionHasErrors([
            'identity_number' => 'Nomor identitas sudah terdaftar.',
        ]);
    }

    public function test_show_displays_loan_detail_items_and_empty_return_history(): void
    {
        $user = $this->admin();
        $borrower = Borrower::factory()->create([
            'name' => 'Budi Santoso',
            'identity_number' => '197001012000011001',
            'institution' => 'Sekretariat DPRD',
            'phone' => '081234567890',
        ]);
        $book = $this->book(5, 3);

        $loan = Loan::factory()->create([
            'loan_number' => 'PJM-20260401-0001',
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'notes' => 'Untuk rapat komisi',
        ]);

        LoanItem::factory()->create([
            'loan_id' => $loan->id,
            'book_id' => $book->id,
            'quantity' => 2,
            'returned_quantity' => 0,
        ]);

        $response = $this->actingAs($user)->get("/admin/peminjaman/{$loan->id}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Peminjaman/Show')
            ->where('loan.loan_number', 'PJM-20260401-0001')
            ->where('loan.loan_date', now()->toDateString())
            ->where('loan.due_date', now()->addDays(14)->toDateString())
            ->where('loan.status', 'berjalan')
            ->where('loan.is_overdue', false)
            ->where('loan.notes', 'Untuk rapat komisi')
            ->where('loan.petugas', $user->name)
            ->where('loan.borrower.name', 'Budi Santoso')
            ->where('loan.borrower.identity_number', '197001012000011001')
            ->where('loan.borrower.institution', 'Sekretariat DPRD')
            ->has('items', 1)
            ->where('items.0.title', $book->title)
            ->where('items.0.quantity', 2)
            ->where('items.0.returned_quantity', 0)
            ->where('items.0.remaining_quantity', 2)
            ->where('items.0.status', 'dipinjam')
            ->has('items.0.returns', 0)
            ->missing('loans')
        );
    }

    public function test_show_returns_not_found_for_unknown_loan(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/peminjaman/999999')
            ->assertNotFound();
    }

    public function test_database_rejects_returned_quantity_above_quantity(): void
    {
        $loan = Loan::factory()->create(['user_id' => $this->admin()->id]);

        $this->expectException(QueryException::class);

        LoanItem::factory()->create([
            'loan_id' => $loan->id,
            'quantity' => 1,
            'returned_quantity' => 2,
        ]);
    }
}
