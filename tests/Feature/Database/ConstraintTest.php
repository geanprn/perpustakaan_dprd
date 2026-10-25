<?php

namespace Tests\Feature\Database;

use App\Models\Book;
use App\Models\Borrower;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\ReturnItem;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConstraintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('CHECK constraint hanya diuji di MySQL/MariaDB.');
        }
    }

    private function makeLoan(Category $category): Loan
    {
        $user = User::factory()->create();
        $borrower = Borrower::factory()->create();

        return Loan::factory()->create([
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_check_constraints_registered(): void
    {
        $names = collect(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_TYPE = 'CHECK' AND TABLE_SCHEMA = DATABASE()"
        ))->pluck('CONSTRAINT_NAME');

        $this->assertContains('books_total_copies_ge_0', $names);
        $this->assertContains('books_available_copies_ge_0', $names);
        $this->assertContains('books_available_copies_le_total', $names);
        $this->assertContains('loan_items_quantity_gt_0', $names);
        $this->assertContains('loan_items_returned_quantity_ge_0', $names);
        $this->assertContains('loan_items_returned_quantity_le_quantity', $names);
        $this->assertContains('returns_quantity_gt_0', $names);
    }

    public function test_foreign_keys_use_restrict_on_delete(): void
    {
        $rules = collect(DB::select(
            'SELECT TABLE_NAME, REFERENCED_TABLE_NAME, DELETE_RULE
             FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()'
        ));

        foreach ($rules as $rule) {
            $this->assertSame('RESTRICT', $rule->DELETE_RULE, "{$rule->TABLE_NAME} tidak RESTRICT");
        }
    }

    public function test_available_copies_cannot_exceed_total_copies(): void
    {
        $category = Category::factory()->create();

        $this->expectException(QueryException::class);

        Book::factory()->create([
            'category_id' => $category->id,
            'total_copies' => 1,
            'available_copies' => 5,
        ]);
    }

    public function test_total_copies_cannot_be_negative(): void
    {
        $category = Category::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('books')->insert([
            'category_id' => $category->id,
            'title' => 'Negatif', 'author' => 'X',
            'total_copies' => -1, 'available_copies' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_loan_item_quantity_must_be_positive(): void
    {
        $loan = $this->makeLoan(Category::factory()->create());
        $book = Book::factory()->create();

        $this->expectException(QueryException::class);

        LoanItem::factory()->create([
            'loan_id' => $loan->id,
            'book_id' => $book->id,
            'quantity' => 0,
        ]);
    }

    public function test_returned_quantity_cannot_exceed_quantity(): void
    {
        $loan = $this->makeLoan(Category::factory()->create());
        $book = Book::factory()->create();

        $this->expectException(QueryException::class);

        LoanItem::factory()->create([
            'loan_id' => $loan->id,
            'book_id' => $book->id,
            'quantity' => 2,
            'returned_quantity' => 3,
        ]);
    }

    public function test_return_quantity_must_be_positive(): void
    {
        $loan = $this->makeLoan(Category::factory()->create());
        $book = Book::factory()->create();
        $item = LoanItem::factory()->create([
            'loan_id' => $loan->id,
            'book_id' => $book->id,
            'quantity' => 2,
        ]);

        $this->expectException(QueryException::class);

        ReturnItem::factory()->create([
            'loan_item_id' => $item->id,
            'quantity' => 0,
        ]);
    }

    public function test_category_with_books_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Book::factory()->create(['category_id' => $category->id]);

        $this->expectException(QueryException::class);

        $category->delete();
    }

    public function test_borrower_with_loans_cannot_be_deleted(): void
    {
        $loan = $this->makeLoan(Category::factory()->create());

        $this->expectException(QueryException::class);

        $loan->borrower->delete();
    }

    public function test_book_with_loan_items_cannot_be_deleted(): void
    {
        $loan = $this->makeLoan(Category::factory()->create());
        $book = Book::factory()->create();
        LoanItem::factory()->create(['loan_id' => $loan->id, 'book_id' => $book->id]);

        $this->expectException(QueryException::class);

        $book->delete();
    }
}
