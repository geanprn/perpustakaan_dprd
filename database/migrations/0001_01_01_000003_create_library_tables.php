<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Apakah driver mendukung ALTER TABLE ... ADD CONSTRAINT CHECK dan FULLTEXT.
     * Hanya MySQL/MariaDB yang mendukung sintaks ini (PRD: MySQL 8).
     */
    private function supportsCheckConstraints(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('author');
            $table->string('publisher')->nullable();
            $table->unsignedSmallInteger('publication_year')->nullable();
            $table->string('shelf_location')->nullable();
            $table->unsignedInteger('total_copies')->default(0);
            $table->unsignedInteger('available_copies')->default(0);
            $table->timestamps();

            $table->index('title');
            $table->index('author');
            $table->index('category_id');
        });

        Schema::create('borrowers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('identity_number')->unique();
            $table->string('institution')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('identity_number');
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_number')->unique();
            $table->foreignId('borrower_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('loan_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['berjalan', 'selesai'])->default('berjalan');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('loan_date');
            $table->index('due_date');
        });

        Schema::create('loan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->restrictOnDelete();
            $table->foreignId('book_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('returned_quantity')->default(0);
            $table->enum('status', ['dipinjam', 'selesai'])->default('dipinjam');
            $table->timestamps();
        });

        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('return_date');
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();

            $table->index('return_date');
        });

        if (! $this->supportsCheckConstraints()) {
            return;
        }

        // FULLTEXT disiapkan untuk pencarian; default tetap LIKE + indeks biasa.
        DB::statement('
            ALTER TABLE books
            ADD FULLTEXT fulltext_books_title_author (title, author)
        ');

        DB::statement('
            ALTER TABLE books
            ADD CONSTRAINT books_total_copies_ge_0 CHECK (total_copies >= 0),
            ADD CONSTRAINT books_available_copies_ge_0 CHECK (available_copies >= 0),
            ADD CONSTRAINT books_available_copies_le_total CHECK (available_copies <= total_copies)
        ');

        DB::statement('
            ALTER TABLE loan_items
            ADD CONSTRAINT loan_items_quantity_gt_0 CHECK (quantity > 0),
            ADD CONSTRAINT loan_items_returned_quantity_ge_0 CHECK (returned_quantity >= 0),
            ADD CONSTRAINT loan_items_returned_quantity_le_quantity CHECK (returned_quantity <= quantity)
        ');

        DB::statement('
            ALTER TABLE returns
            ADD CONSTRAINT returns_quantity_gt_0 CHECK (quantity > 0)
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->supportsCheckConstraints()) {
            DB::statement('ALTER TABLE returns DROP CONSTRAINT returns_quantity_gt_0');
            DB::statement('ALTER TABLE loan_items DROP CONSTRAINT loan_items_quantity_gt_0');
            DB::statement('ALTER TABLE loan_items DROP CONSTRAINT loan_items_returned_quantity_ge_0');
            DB::statement('ALTER TABLE loan_items DROP CONSTRAINT loan_items_returned_quantity_le_quantity');
            DB::statement('ALTER TABLE books DROP CONSTRAINT books_total_copies_ge_0');
            DB::statement('ALTER TABLE books DROP CONSTRAINT books_available_copies_ge_0');
            DB::statement('ALTER TABLE books DROP CONSTRAINT books_available_copies_le_total');
            DB::statement('ALTER TABLE books DROP FULLTEXT fulltext_books_title_author');
        }

        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('returns');
        Schema::dropIfExists('loan_items');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('borrowers');
        Schema::dropIfExists('books');
        Schema::dropIfExists('categories');

        Schema::enableForeignKeyConstraints();
    }
};
