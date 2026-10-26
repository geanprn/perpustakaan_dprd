<?php

namespace App\Actions\Loans;

use App\Enums\LoanItemStatus;
use App\Enums\LoanStatus;
use App\Models\Book;
use App\Models\Loan;
use App\Models\LoanItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateLoanAction
{
    /**
     * Jumlah maksimum percobaan saat nomor transaksi bertabrakan.
     */
    private const MAX_ATTEMPTS = 3;

    /**
     * Catat transaksi peminjaman beserta rincian buku secara atomik
     * (FR-LN-06, FR-LN-07, BR-04, BR-05, BR-09).
     *
     * Seluruh perubahan stok dan pencatatan transaksi terjadi dalam satu
     * DB::transaction (BR-09). Baris buku dikunci dengan lockForUpdate dan
     * diurutkan berdasarkan id agar tidak terjadi deadlock saat dua petugas
     * mencatat bersamaan (9.3).
     *
     * Bila stok salah satu judul tidak mencukupi, ValidationException
     * dilempar sehingga seluruh perubahan dibatalkan (skenario 2).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function execute(array $data): Loan
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                return DB::transaction(fn (): Loan => $this->createLoan($data));
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt >= self::MAX_ATTEMPTS) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * Buat transaksi di dalam transaksi database yang sudah aktif.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function createLoan(array $data): Loan
    {
        /** @var array<int, array{book_id: int|string, quantity: int|string}> $items */
        $items = array_values($data['items']);

        $bookIds = array_map(
            static fn (array $item): int => (int) $item['book_id'],
            $items,
        );

        sort($bookIds);

        /** @var array<int, Book> $books */
        $books = Book::query()
            ->whereIn('id', $bookIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (Book $book): int => (int) $book->getKey())
            ->all();

        $this->assertStockIsSufficient($items, $books);

        $loanDate = CarbonImmutable::parse($data['loan_date']);
        $dueDate = filled($data['due_date'] ?? null)
            ? CarbonImmutable::parse($data['due_date'])
            : null;

        $loan = Loan::query()->create([
            'loan_number' => $this->nextLoanNumber($loanDate),
            'borrower_id' => (int) $data['borrower_id'],
            'user_id' => (int) $data['user_id'],
            'loan_date' => $loanDate->toDateString(),
            'due_date' => $dueDate?->toDateString(),
            'status' => LoanStatus::berjalan->value,
            'notes' => filled($data['notes'] ?? null) ? $data['notes'] : null,
        ]);

        foreach ($items as $item) {
            $bookId = (int) $item['book_id'];
            $quantity = (int) $item['quantity'];

            LoanItem::query()->create([
                'loan_id' => $loan->getKey(),
                'book_id' => $bookId,
                'quantity' => $quantity,
                'returned_quantity' => 0,
                'status' => LoanItemStatus::dipinjam->value,
            ]);

            $book = $books[$bookId];
            $book->available_copies = (int) $book->available_copies - $quantity;
            $book->save();
        }

        return $loan;
    }

    /**
     * Pastikan stok tiap judul mencukupi; kumpulkan galat per baris rincian
     * agar pengguna tahu judul mana yang kurang (FR-LN-03, skenario 2).
     *
     * @param  array<int, array{book_id: int|string, quantity: int|string}>  $items
     * @param  array<int, Book>  $books
     *
     * @throws ValidationException
     */
    private function assertStockIsSufficient(array $items, array $books): void
    {
        $errors = [];

        foreach ($items as $index => $item) {
            $bookId = (int) $item['book_id'];
            $quantity = (int) $item['quantity'];

            $book = $books[$bookId];

            if ($quantity <= (int) $book->available_copies) {
                continue;
            }

            $errors["items.{$index}.quantity"] = sprintf(
                'Stok buku "%s" tidak mencukupi. Tersisa %d eksemplar, diminta %d eksemplar.',
                $book->title,
                (int) $book->available_copies,
                $quantity,
            );
        }

        if ($errors === []) {
            return;
        }

        throw ValidationException::withMessages($errors);
    }

    /**
     * Hasilkan nomor transaksi unik berformat PJM-YYYYMMDD-NNNN.
     *
     * Urutan dihitung dari jumlah nomor dengan awalan tanggal yang sama di
     * dalam transaksi database. Bila dua permintaan berjalan bersamaan dan
     * menghasilkan nomor yang sama, pelanggaran indeks unik ditangkap oleh
     * pemanggil dan percobaan diulang.
     */
    private function nextLoanNumber(CarbonImmutable $loanDate): string
    {
        $prefix = 'PJM-'.$loanDate->format('Ymd').'-';

        $urutan = Loan::query()
            ->where('loan_number', 'like', $prefix.'%')
            ->count() + 1;

        return sprintf('%s%04d', $prefix, $urutan);
    }
}
