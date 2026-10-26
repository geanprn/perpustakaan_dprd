<?php

namespace App\Support;

use App\Models\Book;

/**
 * Pemeta kolom publik untuk katalog.
 *
 * Hanya kolom yang dibutuhkan pengunjung yang dikirim ke Inertia, sehingga
 * model utuh (beserta relasi internal) tidak pernah bocor ke halaman publik
 * (PRD 10 — tidak ada data sensitif di katalog publik).
 */
final class CatalogPresenter
{
    /**
     * Data buku untuk daftar/kartu katalog.
     *
     * Stok tersedia hanya dikirim sebagai angka bila
     * config library.show_exact_stock bernilai true.
     *
     * @return array<string, mixed>
     */
    public static function forList(Book $book): array
    {
        return [
            'id' => (int) $book->getKey(),
            'title' => $book->title,
            'author' => $book->author,
            'category' => $book->category?->name,
            'shelf_location' => $book->shelf_location,
            'total_copies' => (int) $book->total_copies,
            'available_copies' => self::exactStock($book),
            'is_available' => (int) $book->available_copies > 0,
        ];
    }

    /**
     * Data buku untuk halaman detail — menambahkan penerbit dan tahun terbit.
     *
     * @return array<string, mixed>
     */
    public static function forDetail(Book $book): array
    {
        return [
            ...self::forList($book),
            'publisher' => $book->publisher,
            'publication_year' => $book->publication_year === null
                ? null
                : (int) $book->publication_year,
        ];
    }

    /**
     * Apakah stok persis boleh ditampilkan ke pengunjung.
     */
    public static function showExactStock(): bool
    {
        return (bool) config('library.show_exact_stock');
    }

    private static function exactStock(Book $book): ?int
    {
        return self::showExactStock() ? (int) $book->available_copies : null;
    }
}
