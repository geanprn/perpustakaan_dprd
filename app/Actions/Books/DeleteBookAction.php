<?php

namespace App\Actions\Books;

use App\Models\Book;
use Illuminate\Support\Facades\DB;

class DeleteBookAction
{
    /**
     * Hapus buku.
     *
     * Mengembalikan false bila buku sudah punya riwayat peminjaman (BR-08);
     * riwayat transaksi tidak boleh hilang.
     */
    public function execute(Book $book): bool
    {
        return DB::transaction(function () use ($book): bool {
            /** @var Book $locked */
            $locked = Book::query()->lockForUpdate()->findOrFail($book->getKey());

            if ($locked->loanItems()->exists()) {
                return false;
            }

            $locked->delete();

            return true;
        });
    }
}
