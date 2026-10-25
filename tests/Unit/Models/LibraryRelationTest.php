<?php

namespace Tests\Unit\Models;

use App\Enums\LoanItemStatus;
use App\Enums\LoanStatus;
use App\Models\Book;
use App\Models\Borrower;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\ReturnItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LibraryRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_has_many_books(): void
    {
        $category = Category::factory()->create();
        Book::factory()->count(3)->create(['category_id' => $category->id]);

        $this->assertCount(3, $category->fresh()->books);
        $this->assertInstanceOf(Book::class, $category->books->first());
    }

    public function test_book_belongs_to_category_and_has_many_loan_items(): void
    {
        $book = Book::factory()->create();
        LoanItem::factory()->create(['book_id' => $book->id]);

        $this->assertInstanceOf(Category::class, $book->category);
        $this->assertCount(1, $book->fresh()->loanItems);
    }

    public function test_borrower_has_many_loans(): void
    {
        $borrower = Borrower::factory()->create();
        Loan::factory()->count(2)->create(['borrower_id' => $borrower->id]);

        $this->assertCount(2, $borrower->fresh()->loans);
    }

    public function test_loan_relations_and_enum_casts(): void
    {
        $loan = Loan::factory()->create();

        $this->assertInstanceOf(Borrower::class, $loan->borrower);
        $this->assertInstanceOf(User::class, $loan->user);
        $this->assertInstanceOf(LoanStatus::class, $loan->status);
        $this->assertSame('berjalan', $loan->status->value);
        $this->assertInstanceOf(Carbon::class, $loan->loan_date);
    }

    public function test_loan_has_many_loan_items(): void
    {
        $loan = Loan::factory()->create();
        LoanItem::factory()->count(2)->create(['loan_id' => $loan->id]);

        $this->assertCount(2, $loan->fresh()->loanItems);
    }

    public function test_loan_item_relations_and_enum_casts(): void
    {
        $item = LoanItem::factory()->create();

        $this->assertInstanceOf(Loan::class, $item->loan);
        $this->assertInstanceOf(Book::class, $item->book);
        $this->assertInstanceOf(LoanItemStatus::class, $item->status);
        $this->assertSame(0, $item->returned_quantity);
    }

    public function test_loan_item_has_many_returns(): void
    {
        $item = LoanItem::factory()->create(['quantity' => 3]);
        ReturnItem::factory()->create(['loan_item_id' => $item->id, 'quantity' => 1]);
        ReturnItem::factory()->create(['loan_item_id' => $item->id, 'quantity' => 2]);

        $returns = $item->fresh()->returns;

        $this->assertCount(2, $returns);
        $this->assertInstanceOf(ReturnItem::class, $returns->first());
        $this->assertSame('returns', $returns->first()->getTable());
    }

    public function test_user_has_many_loans_and_returns(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $loan = Loan::factory()->create(['user_id' => $user->id]);
        $item = LoanItem::factory()->create(['loan_id' => $loan->id, 'quantity' => 2]);
        ReturnItem::factory()->create([
            'user_id' => $user->id,
            'loan_item_id' => $item->id,
            'quantity' => 1,
        ]);

        $this->assertCount(1, $user->fresh()->loans);
        $this->assertCount(1, $user->fresh()->returns);
        $this->assertCount(0, $otherUser->fresh()->loans);
    }

    public function test_book_borrowed_count(): void
    {
        $book = Book::factory()->create(['total_copies' => 5, 'available_copies' => 2]);

        $this->assertSame(3, $book->borrowedCount());
    }
}
