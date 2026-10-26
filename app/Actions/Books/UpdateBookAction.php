<?php

namespace App\Actions\Books;

use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateBookAction
{
    /**
     * Perbarui data buku termasuk penyesuaian stok tersedia.
     *
     * Aturan (FR-BK-06):
     *   available_baru = available_lama + (total_baru - total_lama)
     *
     * Penghapusan ditolak bila total_baru lebih kecil dari jumlah eksemplar
     * yang sedang dipinjam (total_lama - available_lama) — skenario 9.
     *
     * Baris buku dikunci (lockForUpdate) agar dua petugas yang mengubah
     * bersamaan tidak menghasilkan stok salah.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function execute(Book $book, array $data): Book
    {
        return DB::transaction(function () use ($book, $data): Book {
            /** @var Book $locked */
            $locked = Book::query()->lockForUpdate()->findOrFail($book->getKey());

            $totalLama = (int) $locked->total_copies;
            $availableLama = (int) $locked->available_copies;
            $sedangDipinjam = max($totalLama - $availableLama, 0);
            $totalBaru = (int) $data['total_copies'];

            if ($totalBaru < $sedangDipinjam) {
                throw ValidationException::withMessages([
                    'total_copies' => sprintf(
                        'Jumlah eksemplar tidak boleh kurang dari %d eksemplar yang sedang dipinjam.',
                        $sedangDipinjam,
                    ),
                ]);
            }

            $availableBaru = $availableLama + ($totalBaru - $totalLama);

            $locked->fill([
                ...$data,
                'total_copies' => $totalBaru,
                'available_copies' => $availableBaru,
            ])->save();

            return $locked;
        });
    }
}
