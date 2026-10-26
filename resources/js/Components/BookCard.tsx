import Badge from '@/Components/ui/badge';
import { Card, CardContent } from '@/Components/ui/card';
import { Link } from '@inertiajs/react';
import { BookOpen, MapPin } from 'lucide-react';

import type { CatalogBook } from '@/types';

/**
 * Kartu ringkas satu buku pada beranda dan katalog publik.
 *
 * Hanya menampilkan tautan ke detail — tidak ada aksi ubah/hapus/pinjam
 * (FR-PUB-06).
 */
export default function BookCard({ book }: { book: CatalogBook }) {
    return (
        <Card className="h-full transition-shadow hover:shadow-md">
            <CardContent className="flex h-full flex-col gap-3 p-5">
                <div className="flex items-start gap-3">
                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-indigo-50 text-indigo-600">
                        <BookOpen className="h-5 w-5" aria-hidden="true" />
                    </span>

                    <div className="min-w-0 space-y-1">
                        <h3 className="line-clamp-2 text-sm font-semibold text-gray-900">
                            <Link
                                href={route('catalog.show', book.id)}
                                className="hover:text-indigo-700 hover:underline"
                            >
                                {book.title}
                            </Link>
                        </h3>
                        <p className="truncate text-sm text-gray-600">
                            {book.author}
                        </p>
                    </div>
                </div>

                <dl className="space-y-2 text-sm text-gray-600">
                    <div className="flex items-center justify-between gap-3">
                        <dt className="text-gray-500">Kategori</dt>
                        <dd className="truncate font-medium text-gray-800">
                            {book.category ?? 'Tanpa kategori'}
                        </dd>
                    </div>

                    <div className="flex items-center justify-between gap-3">
                        <dt className="text-gray-500">Jumlah eksemplar</dt>
                        <dd className="font-medium text-gray-800">
                            {book.total_copies}
                        </dd>
                    </div>

                    <div className="flex items-center justify-between gap-3">
                        <dt className="text-gray-500">Stok</dt>
                        <dd>
                            <StockBadge book={book} />
                        </dd>
                    </div>
                </dl>

                <div className="mt-auto flex items-center justify-between gap-3 pt-2">
                    <span className="flex items-center gap-1 text-xs text-gray-500">
                        <MapPin className="h-3.5 w-3.5" aria-hidden="true" />
                        {book.shelf_location ?? 'Lokasi belum diatur'}
                    </span>

                    <Link
                        href={route('catalog.show', book.id)}
                        className="text-sm font-medium text-indigo-600 hover:underline"
                    >
                        Lihat detail
                    </Link>
                </div>
            </CardContent>
        </Card>
    );
}

/**
 * Status stok buku.
 *
 * Jika config library.show_exact_stock false, angka stok tidak dikirim server
 * (available_copies bernilai null) sehingga hanya status yang tampil.
 */
function StockBadge({ book }: { book: CatalogBook }) {
    if (book.available_copies === null) {
        return (
            <Badge variant={book.is_available ? 'success' : 'destructive'}>
                {book.is_available ? 'Tersedia' : 'Habis'}
            </Badge>
        );
    }

    return (
        <Badge variant={book.available_copies > 0 ? 'success' : 'destructive'}>
            {book.available_copies > 0
                ? `Tersedia ${book.available_copies}`
                : 'Habis'}
        </Badge>
    );
}
