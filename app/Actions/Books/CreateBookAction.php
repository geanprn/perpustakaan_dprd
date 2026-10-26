<?php

namespace App\Actions\Books;

use App\Models\Book;
use Illuminate\Support\Facades\DB;

class CreateBookAction
{
    /**
     * Buat buku baru.
     *
     * Stok tersedia disamakan dengan jumlah eksemplar karena belum ada
     * peminjaman (BR-03).
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Book
    {
        return DB::transaction(function () use ($data): Book {
            $total = (int) $data['total_copies'];

            $data['total_copies'] = $total;
            $data['available_copies'] = $total;

            return Book::query()->create($data);
        });
    }
}
