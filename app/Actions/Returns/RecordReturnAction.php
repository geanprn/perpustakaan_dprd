<?php

namespace App\Actions\Returns;

use App\Enums\LoanItemStatus;
use App\Enums\LoanStatus;
use App\Models\Book;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\ReturnItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordReturnAction
{
    /**
     * Pesan untuk transaksi yang sudah selesai — dipakai juga oleh controller
     * saat halaman form dibuka untuk transaksi yang sudah selesai.
     */
    public const PESAN_SUDAH_SELESAI = 'Transaksi ini sudah selesai. Pengembalian tidak dapat dicatat lagi.';

    /**
     * Catat pengembalian buku (mendukung sebagian) secara atomik
     * (FR-RT-05 sampai FR-RT-07, BR-06, BR-09).
     *
     * Seluruh pencatatan riwayat, penambahan stok, dan perubahan status
     * terjadi dalam satu DB::transaction (BR-09). Baris transaksi, baris
     * rincian, dan baris buku dikunci dengan lockForUpdate; baris buku
     * diurutkan berdasarkan id agar tidak terjadi deadlock (9.3).
     *
     * Bila salah satu rincian tidak valid, ValidationException dilempar
     * sehingga tidak ada satu pun perubahan yang tersimpan (FR-RT-04).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function execute(array $data): Loan
    {
        return DB::transaction(function () use ($data): Loan {
            $loanId = (int) $data['loan_id'];
            $userId = (int) $data['user_id'];
            $returnDate = CarbonImmutable::parse($data['return_date']);

            /** @var Loan $loan */
            $loan = Loan::query()->lockForUpdate()->findOrFail($loanId);

            if ($loan->status === LoanStatus::selesai) {
                throw ValidationException::withMessages([
                    'items' => self::PESAN_SUDAH_SELESAI,
                ]);
            }

            $items = $this->normalizeItems($data['items'] ?? []);

            $loanItems = LoanItem::query()
                ->with('book:id,title')
                ->whereIn('id', array_column($items, 'loan_item_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy(static fn (LoanItem $loanItem): int => (int) $loanItem->getKey());

            $this->assertItemsAreReturnable($items, $loanItems, $loanId);

            $books = $this->lockBooks($items, $loanItems);

            foreach ($items as $item) {
                /** @var LoanItem $loanItem */
                $loanItem = $loanItems->get($item['loan_item_id']);
                $quantity = $item['quantity'];

                ReturnItem::query()->create([
                    'loan_item_id' => (int) $loanItem->getKey(),
                    'user_id' => $userId,
                    'return_date' => $returnDate->toDateString(),
                    'quantity' => $quantity,
                ]);

                $loanItem->returned_quantity = (int) $loanItem->returned_quantity + $quantity;

                if ((int) $loanItem->returned_quantity >= (int) $loanItem->quantity) {
                    $loanItem->status = LoanItemStatus::selesai;
                }

                $loanItem->save();

                /** @var Book $book */
                $book = $books[(int) $loanItem->book_id];
                $book->available_copies = (int) $book->available_copies + $quantity;
                $book->save();
            }

            $masihAdaRincianBerjalan = LoanItem::query()
                ->where('loan_id', $loanId)
                ->where('status', '!=', LoanItemStatus::selesai->value)
                ->exists();

            if (! $masihAdaRincianBerjalan) {
                $loan->status = LoanStatus::selesai;
                $loan->save();
            }

            return $loan;
        });
    }

    /**
     * Rapikan input rincian: baris dengan jumlah 0 diabaikan sebagai
     * "tidak dikembalikan", jumlah negatif ditolak, dan transaksi tanpa
     * satu pun rincian terisi ditolak (FR-RT-03, FR-RT-04).
     *
     * @param  array<int, array<string, mixed>>  $rawItems
     * @return array<int, array{loan_item_id: int, quantity: int}>
     *
     * @throws ValidationException
     */
    private function normalizeItems(array $rawItems): array
    {
        $items = [];

        foreach (array_values($rawItems) as $index => $rawItem) {
            $quantity = $rawItem['quantity'] ?? 0;
            $quantity = $quantity === '' || $quantity === null ? 0 : (int) $quantity;

            if ($quantity < 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => 'Jumlah pengembalian tidak boleh negatif.',
                ]);
            }

            if ($quantity === 0) {
                continue;
            }

            $items[] = [
                'loan_item_id' => (int) ($rawItem['loan_item_id'] ?? 0),
                'quantity' => $quantity,
            ];
        }

        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'Isi jumlah pengembalian untuk minimal satu judul buku.',
            ]);
        }

        return $items;
    }

    /**
     * Pastikan setiap rincian milik transaksi ini, belum selesai, dan jumlah
     * yang diminta tidak melebihi sisa yang belum dikembalikan (FR-RT-04,
     * skenario 4).
     *
     * @param  array<int, array{loan_item_id: int, quantity: int}>  $items
     * @param  Collection<int, LoanItem>  $loanItems
     *
     * @throws ValidationException
     */
    private function assertItemsAreReturnable(array $items, $loanItems, int $loanId): void
    {
        $errors = [];

        foreach ($items as $index => $item) {
            $loanItem = $loanItems->get($item['loan_item_id']);

            if ($loanItem === null || (int) $loanItem->loan_id !== $loanId) {
                $errors["items.{$index}.loan_item_id"] = 'Rincian peminjaman tidak ditemukan pada transaksi ini.';

                continue;
            }

            $sisa = (int) $loanItem->quantity - (int) $loanItem->returned_quantity;
            $judul = $loanItem->book?->title ?? 'Tanpa judul';

            if ($sisa <= 0) {
                $errors["items.{$index}.quantity"] = sprintf(
                    'Buku "%s" sudah dikembalikan seluruhnya.',
                    $judul,
                );

                continue;
            }

            if ($item['quantity'] > $sisa) {
                $errors["items.{$index}.quantity"] = sprintf(
                    'Jumlah pengembalian buku "%s" melebihi sisa yang belum dikembalikan. Sisa %d eksemplar, diminta %d eksemplar.',
                    $judul,
                    $sisa,
                    $item['quantity'],
                );
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Kunci baris buku yang stoknya akan ditambah, diurutkan berdasarkan id.
     *
     * @param  array<int, array{loan_item_id: int, quantity: int}>  $items
     * @param  Collection<int, LoanItem>  $loanItems
     * @return array<int, Book>
     */
    private function lockBooks(array $items, $loanItems): array
    {
        $bookIds = [];

        foreach ($items as $item) {
            /** @var LoanItem $loanItem */
            $loanItem = $loanItems->get($item['loan_item_id']);
            $bookIds[] = (int) $loanItem->book_id;
        }

        $bookIds = array_values(array_unique($bookIds));
        sort($bookIds);

        return Book::query()
            ->whereIn('id', $bookIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy(static fn (Book $book): int => (int) $book->getKey())
            ->all();
    }
}
