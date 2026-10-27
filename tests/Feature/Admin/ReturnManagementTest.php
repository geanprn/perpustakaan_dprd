<?php

namespace Tests\Feature\Admin;

use App\Actions\Loans\CreateLoanAction;
use App\Actions\Returns\RecordReturnAction;
use App\Enums\LoanItemStatus;
use App\Enums\LoanStatus;
use App\Models\Book;
use App\Models\Borrower;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\ReturnItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReturnManagementTest extends TestCase
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
     * Buat transaksi berjalan beserta satu rincian pada buku tertentu.
     */
    private function loanWithItem(
        User $user,
        Book $book,
        int $quantity,
        array $loanOverrides = [],
    ): Loan {
        $loan = Loan::factory()->create([
            'borrower_id' => Borrower::factory()->create()->id,
            'user_id' => $user->id,
            'loan_date' => now()->toDateString(),
            'due_date' => null,
            'status' => LoanStatus::berjalan->value,
            ...$loanOverrides,
        ]);

        LoanItem::factory()->create([
            'loan_id' => $loan->id,
            'book_id' => $book->id,
            'quantity' => $quantity,
            'returned_quantity' => 0,
            'status' => LoanItemStatus::dipinjam->value,
        ]);

        // Stok tersedia disesuaikan: jumlah eksemplar dikurangi yang dipinjam.
        $book->available_copies = max((int) $book->total_copies - $quantity, 0);
        $book->save();

        return $loan->fresh();
    }

    public function test_guest_cannot_access_return_routes(): void
    {
        $loan = Loan::factory()->create();

        $this->get('/admin/pengembalian')->assertRedirect('/login');
        $this->get("/admin/pengembalian/{$loan->id}")->assertRedirect('/login');
        $this->post("/admin/pengembalian/{$loan->id}", [])->assertRedirect('/login');
    }

    public function test_partial_return_adds_stock_and_keeps_item_in_progress(): void
    {
        $user = $this->admin();
        $book = $this->book(5, 2);
        $loan = $this->loanWithItem($user, $book, 3);
        $item = $loan->loanItems()->firstOrFail();

        $response = $this->actingAs($user)
            ->from("/admin/pengembalian/{$loan->id}")
            ->post("/admin/pengembalian/{$loan->id}", [
                'return_date' => now()->toDateString(),
                'items' => [
                    ['loan_item_id' => $item->id, 'quantity' => 1],
                ],
            ]);

        $response->assertRedirect("/admin/peminjaman/{$loan->id}");
        $response->assertSessionHas(
            'success',
            'Pengembalian berhasil dicatat untuk transaksi '.$loan->loan_number.'.',
        );

        $this->assertSame(3, (int) $book->fresh()->available_copies);
        $this->assertSame(1, (int) $item->fresh()->returned_quantity);
        $this->assertSame(LoanItemStatus::dipinjam, $item->fresh()->status);
        $this->assertSame(LoanStatus::berjalan, $loan->fresh()->status);

        $this->assertDatabaseHas('returns', [
            'loan_item_id' => $item->id,
            'user_id' => $user->id,
            'return_date' => now()->toDateString(),
            'quantity' => 1,
        ]);
    }

    public function test_rejects_return_exceeding_remaining_quantity(): void
    {
        $user = $this->admin();
        $book = $this->book(5, 3);
        $loan = $this->loanWithItem($user, $book, 3);
        $item = $loan->loanItems()->firstOrFail();

        ReturnItem::factory()->create([
            'loan_item_id' => $item->id,
            'user_id' => $user->id,
            'quantity' => 1,
        ]);

        $item->returned_quantity = 1;
        $item->save();

        $response = $this->actingAs($user)
            ->from("/admin/pengembalian/{$loan->id}")
            ->post("/admin/pengembalian/{$loan->id}", [
                'return_date' => now()->toDateString(),
                'items' => [
                    ['loan_item_id' => $item->id, 'quantity' => 3],
                ],
            ]);

        $response->assertSessionHasErrors([
            'items.0.quantity' => sprintf(
                'Jumlah pengembalian buku "%s" melebihi sisa yang belum dikembalikan. Sisa 2 eksemplar, diminta 3 eksemplar.',
                $book->title,
            ),
        ]);

        $this->assertSame(2, (int) $book->fresh()->available_copies);
        $this->assertSame(1, (int) $item->fresh()->returned_quantity);
        $this->assertDatabaseCount('returns', 1);
    }

    public function test_full_return_completes_item_and_loan(): void
    {
        $user = $this->admin();
        $book = $this->book(3, 0);
        $loan = $this->loanWithItem($user, $book, 3);
        $item = $loan->loanItems()->firstOrFail();

        $response = $this->actingAs($user)
            ->post("/admin/pengembalian/{$loan->id}", [
                'return_date' => now()->toDateString(),
                'items' => [
                    ['loan_item_id' => $item->id, 'quantity' => 3],
                ],
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame(3, (int) $book->fresh()->available_copies);
        $this->assertSame(3, (int) $item->fresh()->returned_quantity);
        $this->assertSame(LoanItemStatus::selesai, $item->fresh()->status);
        $this->assertSame(LoanStatus::selesai, $loan->fresh()->status);
    }

    public function test_loan_stays_in_progress_when_only_some_items_are_complete(): void
    {
        $user = $this->admin();
        $bukuA = $this->book(3, 0);
        $bukuB = $this->book(4, 1);
        $loan = $this->loanWithItem($user, $bukuA, 3);

        $itemB = LoanItem::factory()->create([
            'loan_id' => $loan->id,
            'book_id' => $bukuB->id,
            'quantity' => 3,
            'returned_quantity' => 0,
            'status' => LoanItemStatus::dipinjam->value,
        ]);

        $itemA = $loan->loanItems()->where('book_id', $bukuA->id)->firstOrFail();

        $this->actingAs($user)
            ->post("/admin/pengembalian/{$loan->id}", [
                'return_date' => now()->toDateString(),
                'items' => [
                    ['loan_item_id' => $itemA->id, 'quantity' => 3],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(LoanItemStatus::selesai, $itemA->fresh()->status);
        $this->assertSame(LoanItemStatus::dipinjam, $itemB->fresh()->status);
        $this->assertSame(LoanStatus::berjalan, $loan->fresh()->status);
    }

    public function test_supports_multiple_staged_partial_returns(): void
    {
        $user = $this->admin();
        $book = $this->book(4, 0);
        $loan = $this->loanWithItem($user, $book, 4);
        $item = $loan->loanItems()->firstOrFail();

        foreach ([1, 2] as $jumlah) {
            $this->actingAs($user)
                ->post("/admin/pengembalian/{$loan->id}", [
                    'return_date' => now()->toDateString(),
                    'items' => [
                        ['loan_item_id' => $item->id, 'quantity' => $jumlah],
                    ],
                ])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(3, (int) $item->fresh()->returned_quantity);
        $this->assertSame(LoanItemStatus::dipinjam, $item->fresh()->status);
        $this->assertSame(3, (int) $book->fresh()->available_copies);

        $this->actingAs($user)
            ->post("/admin/pengembalian/{$loan->id}", [
                'return_date' => now()->toDateString(),
                'items' => [
                    ['loan_item_id' => $item->id, 'quantity' => 1],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(4, (int) $item->fresh()->returned_quantity);
        $this->assertSame(LoanItemStatus::selesai, $item->fresh()->status);
        $this->assertSame(LoanStatus::selesai, $loan->fresh()->status);
        $this->assertSame(4, (int) $book->fresh()->available_copies);
        $this->assertDatabaseCount('returns', 3);
    }

    public function test_returns_multiple_items_in_one_submission(): void
    {
        $user = $this->admin();
        $bukuA = $this->book(2, 0);
        $bukuB = $this->book(3, 0);
        $loan = $this->loanWithItem($user, $bukuA, 2);

        $itemB = LoanItem::factory()->create([
            'loan_id' => $loan->id,
            'book_id' => $bukuB->id,
            'quantity' => 3,
            'returned_quantity' => 0,
            'status' => LoanItemStatus::dipinjam->value,
        ]);

        $itemA = $loan->loanItems()->where('book_id', $bukuA->id)->firstOrFail();

        $this->actingAs($user)
            ->post("/admin/pengembalian/{$loan->id}", [
                'return_date' => now()->toDateString(),
                'items' => [
                    ['loan_item_id' => $itemA->id, 'quantity' => 2],
                    ['loan_item_id' => $itemB->id, 'quantity' => 3],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, (int) $bukuA->fresh()->available_copies);
        $this->assertSame(3, (int) $bukuB->fresh()->available_copies);
        $this->assertSame(LoanStatus::selesai, $loan->fresh()->status);
        $this->assertDatabaseCount('returns', 2);
    }

    public function test_rolls_back_everything_when_one_item_is_invalid(): void
    {
        $user = $this->admin();
        $bukuA = $this->book(2, 0);
        $bukuB = $this->book(1, 0);
        $loan = $this->loanWithItem($user, $bukuA, 2);

        $itemB = LoanItem::factory()->create([
            'loan_id' => $loan->id,
            'book_id' => $bukuB->id,
            'quantity' => 1,
            'returned_quantity' => 0,
            'status' => LoanItemStatus::dipinjam->value,
        ]);

        $itemA = $loan->loanItems()->where('book_id', $bukuA->id)->firstOrFail();

        $response = $this->actingAs($user)
            ->from("/admin/pengembalian/{$loan->id}")
            ->post("/admin/pengembalian/{$loan->id}", [
                'return_date' => now()->toDateString(),
                'items' => [
                    ['loan_item_id' => $itemA->id, 'quantity' => 2],
                    ['loan_item_id' => $itemB->id, 'quantity' => 5],
                ],
            ]);

        $response->assertSessionHasErrors('items.1.quantity');

        $this->assertDatabaseCount('returns', 0);
        $this->assertSame(0, (int) $itemA->fresh()->returned_quantity);
        $this->assertSame(0, (int) $itemB->fresh()->returned_quantity);
        $this->assertSame(0, (int) $bukuA->fresh()->available_copies);
        $this->assertSame(0, (int) $bukuB->fresh()->available_copies);
        $this->assertSame(LoanStatus::berjalan, $loan->fresh()->status);
    }

    public function test_rejects_item_from_another_loan(): void
    {
        $user = $this->admin();
        $book = $this->book(5, 2);
        $loan = $this->loanWithItem($user, $book, 3);
        $lain = $this->loanWithItem($user, $this->book(5, 3), 2);

        $itemLain = $lain->loanItems()->firstOrFail();

        $response = $this->actingAs($user)
            ->from("/admin/pengembalian/{$loan->id}")
            ->post("/admin/pengembalian/{$loan->id}", [
                'return_date' => now()->toDateString(),
                'items' => [
                    ['loan_item_id' => $itemLain->id, 'quantity' => 1],
                ],
            ]);

        $response->assertSessionHasErrors([
            'items.0.loan_item_id' => 'Rincian peminjaman tidak ditemukan pada transaksi ini.',
        ]);

        $this->assertDatabaseCount('returns', 0);
        $this->assertSame(0, (int) $itemLain->fresh()->returned_quantity);
    }

    public function test_rejects_when_no_item_is_filled(): void
    {
        $user = $this->admin();
        $book = $this->book(5, 2);
        $loan = $this->loanWithItem($user, $book, 3);
        $item = $loan->loanItems()->firstOrFail();

        $response = $this->actingAs($user)
            ->from("/admin/pengembalian/{$loan->id}")
            ->post("/admin/pengembalian/{$loan->id}", [
                'return_date' => now()->toDateString(),
                'items' => [
                    ['loan_item_id' => $item->id, 'quantity' => 0],
                ],
            ]);

        $response->assertSessionHasErrors([
            'items' => 'Isi jumlah pengembalian untuk minimal satu judul buku.',
        ]);

        $this->assertDatabaseCount('returns', 0);
        $this->assertSame(2, (int) $book->fresh()->available_copies);
    }

    public function test_ignores_zero_rows_when_other_rows_are_filled(): void
    {
        $user = $this->admin();
        $bukuA = $this->book(2, 0);
        $bukuB = $this->book(3, 0);
        $loan = $this->loanWithItem($user, $bukuA, 2);

        $itemB = LoanItem::factory()->create([
            'loan_id' => $loan->id,
            'book_id' => $bukuB->id,
            'quantity' => 3,
            'returned_quantity' => 0,
            'status' => LoanItemStatus::dipinjam->value,
        ]);

        $itemA = $loan->loanItems()->where('book_id', $bukuA->id)->firstOrFail();

        $this->actingAs($user)
            ->post("/admin/pengembalian/{$loan->id}", [
                'return_date' => now()->toDateString(),
                'items' => [
                    ['loan_item_id' => $itemA->id, 'quantity' => 0],
                    ['loan_item_id' => $itemB->id, 'quantity' => 1],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('returns', 1);
        $this->assertSame(0, (int) $itemA->fresh()->returned_quantity);
        $this->assertSame(1, (int) $itemB->fresh()->returned_quantity);
        $this->assertSame(0, (int) $bukuA->fresh()->available_copies);
        $this->assertSame(1, (int) $bukuB->fresh()->available_copies);
    }

    public function test_rejects_negative_quantity(): void
    {
        $user = $this->admin();
        $book = $this->book(5, 2);
        $loan = $this->loanWithItem($user, $book, 3);
        $item = $loan->loanItems()->firstOrFail();

        $response = $this->actingAs($user)
            ->from("/admin/pengembalian/{$loan->id}")
            ->post("/admin/pengembalian/{$loan->id}", [
                'return_date' => now()->toDateString(),
                'items' => [
                    ['loan_item_id' => $item->id, 'quantity' => -1],
                ],
            ]);

        $response->assertSessionHasErrors([
            'items.0.quantity' => 'Jumlah pengembalian tidak boleh negatif.',
        ]);

        $this->assertDatabaseCount('returns', 0);
        $this->assertSame(2, (int) $book->fresh()->available_copies);
    }

    public function test_action_rejects_return_with_negative_quantity(): void
    {
        $user = $this->admin();
        $book = $this->book(5, 2);
        $loan = $this->loanWithItem($user, $book, 3);
        $item = $loan->loanItems()->firstOrFail();

        $this->expectException(ValidationException::class);

        try {
            app(RecordReturnAction::class)->execute([
                'loan_id' => $loan->id,
                'user_id' => $user->id,
                'return_date' => now()->toDateString(),
                'items' => [
                    ['loan_item_id' => $item->id, 'quantity' => -2],
                ],
            ]);
        } finally {
            $this->assertDatabaseCount('returns', 0);
            $this->assertSame(2, (int) $book->fresh()->available_copies);
        }
    }

    public function test_rejects_further_return_after_loan_is_complete(): void
    {
        $user = $this->admin();
        $book = $this->book(3, 0);
        $loan = $this->loanWithItem($user, $book, 3);
        $item = $loan->loanItems()->firstOrFail();

        $this->actingAs($user)
            ->post("/admin/pengembalian/{$loan->id}", [
                'return_date' => now()->toDateString(),
                'items' => [
                    ['loan_item_id' => $item->id, 'quantity' => 3],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(LoanStatus::selesai, $loan->fresh()->status);

        $response = $this->actingAs($user)
            ->from("/admin/pengembalian/{$loan->id}")
            ->post("/admin/pengembalian/{$loan->id}", [
                'return_date' => now()->toDateString(),
                'items' => [
                    ['loan_item_id' => $item->id, 'quantity' => 1],
                ],
            ]);

        $response->assertSessionHasErrors([
            'items' => RecordReturnAction::PESAN_SUDAH_SELESAI,
        ]);

        $this->assertDatabaseCount('returns', 1);
        $this->assertSame(3, (int) $book->fresh()->available_copies);
    }

    public function test_form_page_redirects_when_loan_already_complete(): void
    {
        $user = $this->admin();
        $book = $this->book(3, 0);
        $loan = $this->loanWithItem($user, $book, 3, ['status' => LoanStatus::selesai->value]);

        $response = $this->actingAs($user)->get("/admin/pengembalian/{$loan->id}");

        $response->assertRedirect("/admin/peminjaman/{$loan->id}");
        $response->assertSessionHas('error', RecordReturnAction::PESAN_SUDAH_SELESAI);
    }

    public function test_stock_never_exceeds_total_copies(): void
    {
        $user = $this->admin();
        $book = $this->book(3, 3);
        $borrower = Borrower::factory()->create();

        $loan = app(CreateLoanAction::class)->execute([
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
            'loan_date' => now()->toDateString(),
            'items' => [['book_id' => $book->id, 'quantity' => 3]],
        ]);

        $this->assertSame(0, (int) $book->fresh()->available_copies);

        $item = $loan->loanItems()->firstOrFail();

        $this->actingAs($user)
            ->post("/admin/pengembalian/{$loan->id}", [
                'return_date' => now()->toDateString(),
                'items' => [
                    ['loan_item_id' => $item->id, 'quantity' => 3],
                ],
            ])
            ->assertSessionHasNoErrors();

        $buku = $book->fresh();

        $this->assertSame(3, (int) $buku->available_copies);
        $this->assertLessThanOrEqual(
            (int) $buku->total_copies,
            (int) $buku->available_copies,
        );
    }

    public function test_full_cycle_returns_stock_to_initial_value(): void
    {
        $user = $this->admin();
        $book = $this->book(5, 5);
        $borrower = Borrower::factory()->create();

        $loan = app(CreateLoanAction::class)->execute([
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
            'loan_date' => now()->toDateString(),
            'items' => [['book_id' => $book->id, 'quantity' => 2]],
        ]);

        $this->assertSame(3, (int) $book->fresh()->available_copies);

        $item = $loan->loanItems()->firstOrFail();

        $this->actingAs($user)
            ->post("/admin/pengembalian/{$loan->id}", [
                'return_date' => now()->toDateString(),
                'items' => [
                    ['loan_item_id' => $item->id, 'quantity' => 2],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(5, (int) $book->fresh()->available_copies);
        $this->assertSame(LoanStatus::selesai, $loan->fresh()->status);
        $this->assertSame(LoanItemStatus::selesai, $item->fresh()->status);
    }

    public function test_rejects_return_date_before_loan_date(): void
    {
        $user = $this->admin();
        $book = $this->book(5, 2);
        $loan = $this->loanWithItem($user, $book, 3, [
            'loan_date' => '2026-03-10',
        ]);
        $item = $loan->loanItems()->firstOrFail();

        $response = $this->actingAs($user)
            ->from("/admin/pengembalian/{$loan->id}")
            ->post("/admin/pengembalian/{$loan->id}", [
                'return_date' => '2026-03-09',
                'items' => [
                    ['loan_item_id' => $item->id, 'quantity' => 1],
                ],
            ]);

        $response->assertSessionHasErrors([
            'return_date' => 'Tanggal pengembalian tidak boleh lebih awal dari tanggal peminjaman.',
        ]);

        $this->assertDatabaseCount('returns', 0);
    }

    public function test_rejects_return_date_in_the_future(): void
    {
        $user = $this->admin();
        $book = $this->book(5, 2);
        $loan = $this->loanWithItem($user, $book, 3);
        $item = $loan->loanItems()->firstOrFail();

        $response = $this->actingAs($user)
            ->from("/admin/pengembalian/{$loan->id}")
            ->post("/admin/pengembalian/{$loan->id}", [
                'return_date' => now()->addDay()->toDateString(),
                'items' => [
                    ['loan_item_id' => $item->id, 'quantity' => 1],
                ],
            ]);

        $response->assertSessionHasErrors([
            'return_date' => 'Tanggal pengembalian tidak boleh di masa depan.',
        ]);

        $this->assertDatabaseCount('returns', 0);
    }

    public function test_index_lists_only_unfinished_loans(): void
    {
        $user = $this->admin();
        $berjalan = Loan::factory()->create([
            'loan_number' => 'PJM-BERJALAN',
            'borrower_id' => Borrower::factory()->create(['name' => 'Budi Santoso'])->id,
            'user_id' => $user->id,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => LoanStatus::berjalan->value,
        ]);

        Loan::factory()->create([
            'loan_number' => 'PJM-SELESAI',
            'borrower_id' => Borrower::factory()->create()->id,
            'user_id' => $user->id,
            'status' => LoanStatus::selesai->value,
        ]);

        $response = $this->actingAs($user)->get('/admin/pengembalian');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Pengembalian/Index')
            ->has('loans.data', 1)
            ->where('loans.data.0.loan_number', 'PJM-BERJALAN')
            ->where('loans.data.0.borrower.name', 'Budi Santoso')
            ->where('loans.data.0.is_overdue', false)
            ->where('filters.search', '')
        );

        $this->assertNotNull($berjalan);
    }

    public function test_index_search_matches_loan_number_and_borrower_name(): void
    {
        $user = $this->admin();
        $budi = Borrower::factory()->create(['name' => 'Budi Santoso']);
        $siti = Borrower::factory()->create(['name' => 'Siti Aminah']);

        Loan::factory()->create([
            'loan_number' => 'PJM-20260401-0001',
            'borrower_id' => $budi->id,
            'user_id' => $user->id,
            'status' => LoanStatus::berjalan->value,
        ]);

        Loan::factory()->create([
            'loan_number' => 'PJM-20260401-0002',
            'borrower_id' => $siti->id,
            'user_id' => $user->id,
            'status' => LoanStatus::berjalan->value,
        ]);

        $this->actingAs($user)
            ->get('/admin/pengembalian?search=PJM-20260401-0002')
            ->assertInertia(fn (Assert $page) => $page
                ->has('loans.data', 1)
                ->where('loans.data.0.loan_number', 'PJM-20260401-0002')
            );

        $this->actingAs($user)
            ->get('/admin/pengembalian?search=Siti')
            ->assertInertia(fn (Assert $page) => $page
                ->has('loans.data', 1)
                ->where('loans.data.0.borrower.name', 'Siti Aminah')
            );
    }

    public function test_index_shows_remaining_copies_and_overdue_marker(): void
    {
        $user = $this->admin();
        $book = $this->book(3, 1);
        $loan = $this->loanWithItem($user, $book, 2, [
            'loan_number' => 'PJM-TERLAMBAT',
            'loan_date' => now()->subDays(30)->toDateString(),
            'due_date' => now()->subDays(10)->toDateString(),
        ]);

        $this->actingAs($user)
            ->get('/admin/pengembalian')
            ->assertInertia(fn (Assert $page) => $page
                ->has('loans.data', 1)
                ->where('loans.data.0.loan_number', 'PJM-TERLAMBAT')
                ->where('loans.data.0.is_overdue', true)
                ->where('loans.data.0.jumlah_eksemplar', 2)
                ->where('loans.data.0.sisa_eksemplar', 2)
            );

        $this->assertNotNull($loan);
    }

    public function test_index_paginates_and_keeps_query_string(): void
    {
        $user = $this->admin();
        $borrower = Borrower::factory()->create(['name' => 'Peminjam Uji']);

        Loan::factory()->count(12)->create([
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
            'status' => LoanStatus::berjalan->value,
        ]);

        $response = $this->actingAs($user)
            ->get('/admin/pengembalian?search=Peminjam Uji');

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

    public function test_form_page_shows_item_details_and_remaining_quantity(): void
    {
        $user = $this->admin();
        $book = $this->book(3, 1);
        $loan = $this->loanWithItem($user, $book, 2, [
            'loan_number' => 'PJM-20260401-0001',
        ]);
        $item = $loan->loanItems()->firstOrFail();

        ReturnItem::factory()->create([
            'loan_item_id' => $item->id,
            'user_id' => $user->id,
            'quantity' => 1,
        ]);

        $item->returned_quantity = 1;
        $item->save();

        $response = $this->actingAs($user)->get("/admin/pengembalian/{$loan->id}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Pengembalian/Create')
            ->where('loan.loan_number', 'PJM-20260401-0001')
            ->where('loan.status', 'berjalan')
            ->where('loan.is_overdue', false)
            ->has('items', 1)
            ->where('items.0.id', (int) $item->getKey())
            ->where('items.0.title', $book->title)
            ->where('items.0.quantity', 2)
            ->where('items.0.returned_quantity', 1)
            ->where('items.0.remaining_quantity', 1)
            ->where('defaultReturnDate', now()->toDateString())
        );
    }

    public function test_form_page_hides_items_already_completed(): void
    {
        $user = $this->admin();
        $bukuA = $this->book(2, 0);
        $bukuB = $this->book(3, 1);
        $loan = $this->loanWithItem($user, $bukuA, 2);

        $itemB = LoanItem::factory()->create([
            'loan_id' => $loan->id,
            'book_id' => $bukuB->id,
            'quantity' => 2,
            'returned_quantity' => 0,
            'status' => LoanItemStatus::dipinjam->value,
        ]);

        $itemA = $loan->loanItems()->where('book_id', $bukuA->id)->firstOrFail();
        $itemA->returned_quantity = 2;
        $itemA->status = LoanItemStatus::selesai;
        $itemA->save();

        $this->actingAs($user)
            ->get("/admin/pengembalian/{$loan->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->has('items', 1)
                ->where('items.0.id', (int) $itemB->getKey())
            );
    }

    public function test_loan_detail_shows_return_history_and_active_button(): void
    {
        $user = $this->admin();
        $book = $this->book(3, 1);
        $loan = $this->loanWithItem($user, $book, 2);
        $item = $loan->loanItems()->firstOrFail();

        ReturnItem::factory()->create([
            'loan_item_id' => $item->id,
            'user_id' => $user->id,
            'return_date' => now()->subDay()->toDateString(),
            'quantity' => 1,
        ]);

        $item->returned_quantity = 1;
        $item->save();

        $response = $this->actingAs($user)->get("/admin/peminjaman/{$loan->id}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Peminjaman/Show')
            ->where('loan.status', 'berjalan')
            ->has('items', 1)
            ->where('items.0.returned_quantity', 1)
            ->where('items.0.remaining_quantity', 1)
            ->where('items.0.status', 'dipinjam')
            ->has('items.0.returns', 1)
            ->where('items.0.returns.0.quantity', 1)
            ->where('items.0.returns.0.return_date', now()->subDay()->toDateString())
            ->where('items.0.returns.0.petugas', $user->name)
        );
    }

    public function test_return_form_redirects_for_unknown_loan(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/pengembalian/999999')
            ->assertNotFound();
    }

    public function test_completing_loan_number_two_keeps_other_loan_running(): void
    {
        $user = $this->admin();
        $bukuA = $this->book(2, 0);
        $bukuB = $this->book(2, 0);

        $loanSatu = $this->loanWithItem($user, $bukuA, 2);
        $loanDua = $this->loanWithItem($user, $bukuB, 2);

        $itemSatu = $loanSatu->loanItems()->firstOrFail();

        $this->actingAs($user)
            ->post("/admin/pengembalian/{$loanSatu->id}", [
                'return_date' => now()->toDateString(),
                'items' => [
                    ['loan_item_id' => $itemSatu->id, 'quantity' => 2],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(LoanStatus::selesai, $loanSatu->fresh()->status);
        $this->assertSame(LoanStatus::berjalan, $loanDua->fresh()->status);
    }
}
