import BookCard from '@/Components/BookCard';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import Pagination from '@/Components/ui/pagination';
import { Select } from '@/Components/ui/select';
import PublicLayout from '@/Layouts/PublicLayout';
import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

import type { CatalogBook, CatalogCategory, Paginated } from '@/types';

interface KatalogIndexProps {
    books: Paginated<CatalogBook>;
    categories: CatalogCategory[];
    filters: {
        search: string;
        category: number | null;
    };
}

/**
 * Katalog publik — daftar buku dengan pencarian dan filter kategori
 * (FR-PUB-02, FR-PUB-03, FR-PUB-04, FR-PUB-07).
 */
export default function KatalogIndex({
    books,
    categories,
    filters,
}: KatalogIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [category, setCategory] = useState(
        filters.category === null ? '' : String(filters.category),
    );

    useEffect(() => {
        setSearch(filters.search);
        setCategory(filters.category === null ? '' : String(filters.category));
    }, [filters.search, filters.category]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        const params: Record<string, string | number> = {};

        if (search.trim() !== '') {
            params.search = search.trim();
        }

        if (category !== '') {
            params.category = Number(category);
        }

        router.get(route('catalog.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const resetFilters = () => {
        setSearch('');
        setCategory('');
        router.get(route('catalog.index'), {}, { replace: true });
    };

    const hasFilter = filters.search !== '' || filters.category !== null;

    return (
        <PublicLayout title="Katalog">
            <div className="space-y-6">
                <header className="space-y-1">
                    <h1 className="text-xl font-bold text-gray-900 sm:text-2xl">
                        Katalog Buku
                    </h1>
                    <p className="text-sm text-gray-600">
                        Telusuri koleksi perpustakaan berdasarkan judul,
                        pengarang, atau kategori.
                    </p>
                </header>

                <form
                    onSubmit={submit}
                    className="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 sm:grid-cols-[1fr_auto_auto] sm:items-end"
                    role="search"
                >
                    <div>
                        <Label htmlFor="search">Kata kunci</Label>
                        <div className="relative mt-1">
                            <Search
                                className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                                aria-hidden="true"
                            />
                            <Input
                                id="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Judul, pengarang, atau kategori..."
                                className="pl-9"
                            />
                        </div>
                    </div>

                    <div>
                        <Label htmlFor="category">Kategori</Label>
                        <Select
                            id="category"
                            className="mt-1 sm:w-56"
                            value={category}
                            onChange={(e) => setCategory(e.target.value)}
                        >
                            <option value="">Semua kategori</option>
                            {categories.map((item) => (
                                <option key={item.id} value={String(item.id)}>
                                    {item.name} ({item.books_count})
                                </option>
                            ))}
                        </Select>
                    </div>

                    <div className="flex gap-2">
                        <Button type="submit">Cari</Button>

                        {hasFilter && (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={resetFilters}
                            >
                                <X className="h-4 w-4" />
                                Reset
                            </Button>
                        )}
                    </div>
                </form>

                <p className="text-sm text-gray-600" aria-live="polite">
                    Menampilkan {books.data.length} dari {books.total} judul
                    {hasFilter && ' sesuai pencarian Anda'}.
                </p>

                {books.data.length === 0 ? (
                    <EmptyState hasFilter={hasFilter} onReset={resetFilters} />
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {books.data.map((book) => (
                            <BookCard key={book.id} book={book} />
                        ))}
                    </div>
                )}

                <Pagination links={books.links} className="pt-2" />
            </div>
        </PublicLayout>
    );
}

/**
 * Pesan kosong yang jelas bila pencarian tidak menemukan hasil (FR-PUB-07).
 */
function EmptyState({
    hasFilter,
    onReset,
}: {
    hasFilter: boolean;
    onReset: () => void;
}) {
    return (
        <div className="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <p className="text-sm font-medium text-gray-900">
                {hasFilter
                    ? 'Tidak ada buku yang cocok dengan pencarian Anda.'
                    : 'Belum ada buku pada katalog.'}
            </p>

            <p className="mx-auto mt-2 max-w-md text-sm text-gray-600">
                {hasFilter
                    ? 'Coba gunakan kata kunci lain, periksa ejaan, atau kosongkan filter kategori.'
                    : 'Koleksi akan tampil di sini setelah petugas menambahkan data buku.'}
            </p>

            {hasFilter && (
                <Button
                    variant="outline"
                    className="mt-4"
                    onClick={onReset}
                    type="button"
                >
                    Kosongkan pencarian
                </Button>
            )}
        </div>
    );
}
