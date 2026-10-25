<?php

namespace Tests\Feature\Database;

use App\Models\Book;
use App\Models\Borrower;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_library_tables_exist(): void
    {
        foreach (['categories', 'books', 'borrowers', 'loans', 'loan_items', 'returns'] as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Tabel {$table} tidak ada.",
            );
        }
    }

    public function test_users_identity_number_unique(): void
    {
        Borrower::factory()->create(['identity_number' => '12345']);

        $this->expectException(QueryException::class);

        Borrower::factory()->create(['identity_number' => '12345']);
    }

    public function test_category_name_unique(): void
    {
        Category::factory()->create(['name' => 'Fiksi']);

        $this->expectException(QueryException::class);

        Category::factory()->create(['name' => 'Fiksi']);
    }

    public function test_book_nullable_columns_accept_null(): void
    {
        $book = Book::factory()->create([
            'publisher' => null,
            'publication_year' => null,
            'shelf_location' => null,
        ]);

        $this->assertNull($book->fresh()->publisher);
        $this->assertNull($book->fresh()->publication_year);
        $this->assertNull($book->fresh()->shelf_location);
    }
}
