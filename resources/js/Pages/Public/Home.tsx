import BookCard from '@/Components/BookCard';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import PublicLayout from '@/Layouts/PublicLayout';
import { Link, router } from '@inertiajs/react';
import { BookOpen, Library, Search, Tags } from 'lucide-react';
import { FormEventHandler, ReactNode, useState } from 'react';

import type { CatalogBook, CatalogCategory } from '@/types';

interface HomeProps {
    latestBooks: CatalogBook[];
    categories: CatalogCategory[];
    totalBooks: number;
}

/**
 * Beranda publik — pengantar perpustakaan, kotak pencarian, dan tautan
 * katalog (FR-PUB-01).
 */
export default function Home({
    latestBooks,
    categories,
    totalBooks,
}: HomeProps) {
    const [search, setSearch] = useState('');

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        router.get(route('catalog.index'), search.trim() === '' ? {} : { search: search.trim() });
    };

    return (
        <PublicLayout title="Beranda">
            <div className="space-y-10">
                <section className="rounded-xl border border-gray-200 bg-white px-6 py-10 sm:px-10 sm:py-14">
                    <p className="text-sm font-medium uppercase tracking-wide text-indigo-600">
                        Perpustakaan DPRD Kota Banjarmasin
                    </p>

                    <h1 className="mt-3 max-w-3xl text-2xl font-bold leading-tight text-gray-900 sm:text-4xl">
                        Temukan koleksi bacaan yang Anda butuhkan.
                    </h1>

                    <p className="mt-4 max-w-2xl text-sm leading-relaxed text-gray-600 sm:text-base">
                        Perpustakaan DPRD Kota Banjarmasin menyediakan koleksi
                        buku referensi, umum, dan daerah untuk mendukung
                        kegiatan kedewanan serta layanan bagi masyarakat. Telusuri
                        judul, pengarang, atau kategori melalui katalog kami.
                    </p>

                    <form
                        onSubmit={submit}
                        className="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center"
                        role="search"
                    >
                        <div className="relative flex-1">
                            <Search
                                className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                                aria-hidden="true"
                            />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari judul, pengarang, atau kategori..."
                                className="h-11 pl-9"
                                aria-label="Cari koleksi buku"
                            />
                        </div>

                        <Button type="submit" size="lg" className="h-11">
                            Cari Buku
                        </Button>
                    </form>

                    <div className="mt-6 flex flex-wrap items-center gap-4 text-sm text-gray-600">
                        <Link
                            href={route('catalog.index')}
                            className="font-medium text-indigo-600 hover:underline"
                        >
                            Lihat seluruh katalog
                        </Link>

                        <span className="hidden h-4 w-px bg-gray-300 sm:block" />

                        <span>{totalBooks} judul tersedia di katalog</span>
                    </div>
                </section>

                <section className="grid gap-4 sm:grid-cols-3">
                    <InfoTile
                        icon={<BookOpen className="h-5 w-5" />}
                        title="Koleksi buku"
                        description="Buku umum, referensi, dan daerah yang tersusun per kategori."
                    />
                    <InfoTile
                        icon={<Tags className="h-5 w-5" />}
                        title="Kategori jelas"
                        description="Telusuri koleksi melalui kategori agar pencarian lebih cepat."
                    />
                    <InfoTile
                        icon={<Library className="h-5 w-5" />}
                        title="Informasi stok"
                        description="Status ketersediaan setiap judul dapat dilihat langsung."
                    />
                </section>

                {categories.length > 0 && (
                    <section className="space-y-4">
                        <div className="flex items-end justify-between gap-4">
                            <div>
                                <h2 className="text-lg font-semibold text-gray-900">
                                    Jelajahi Kategori
                                </h2>
                                <p className="text-sm text-gray-600">
                                    Pilih kategori untuk memfilter katalog.
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            {categories.map((category) => (
                                <Link
                                    key={category.id}
                                    href={route('catalog.index', {
                                        category: category.id,
                                    })}
                                    className="rounded-full border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 transition-colors hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700"
                                >
                                    {category.name}
                                    <span className="ml-2 text-xs text-gray-500">
                                        {category.books_count}
                                    </span>
                                </Link>
                            ))}
                        </div>
                    </section>
                )}

                {latestBooks.length > 0 && (
                    <section className="space-y-4">
                        <div className="flex items-end justify-between gap-4">
                            <div>
                                <h2 className="text-lg font-semibold text-gray-900">
                                    Buku Terbaru
                                </h2>
                                <p className="text-sm text-gray-600">
                                    Enam judul terakhir yang ditambahkan.
                                </p>
                            </div>

                            <Link
                                href={route('catalog.index')}
                                className="text-sm font-medium text-indigo-600 hover:underline"
                            >
                                Semua buku
                            </Link>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {latestBooks.map((book) => (
                                <BookCard key={book.id} book={book} />
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </PublicLayout>
    );
}

function InfoTile({
    icon,
    title,
    description,
}: {
    icon: ReactNode;
    title: string;
    description: string;
}) {
    return (
        <Card>
            <CardContent className="space-y-2 p-5">
                <span className="flex h-10 w-10 items-center justify-center rounded-md bg-indigo-50 text-indigo-600">
                    {icon}
                </span>
                <h3 className="text-sm font-semibold text-gray-900">{title}</h3>
                <p className="text-sm text-gray-600">{description}</p>
            </CardContent>
        </Card>
    );
}
