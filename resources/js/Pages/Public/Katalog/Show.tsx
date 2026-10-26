import Badge from '@/Components/ui/badge';
import { buttonVariants } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { cn } from '@/lib/utils';
import PublicLayout from '@/Layouts/PublicLayout';
import { Link } from '@inertiajs/react';
import { ArrowLeft, BookOpen, CheckCircle2, XCircle } from 'lucide-react';

import type { CatalogBookDetail } from '@/types';

interface ShowProps {
    book: CatalogBookDetail;
}

/**
 * Detail buku publik (FR-PUB-05).
 *
 * Hanya menampilkan data bacaan; tidak ada aksi ubah/hapus/pinjam (FR-PUB-06).
 */
export default function KatalogShow({ book }: ShowProps) {
    const tersedia = book.available_copies === null
        ? book.is_available
        : book.available_copies > 0;

    return (
        <PublicLayout title={book.title}>
            <div className="space-y-6">
                <Link
                    href={route('catalog.index')}
                    className="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:underline"
                >
                    <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                    Kembali ke katalog
                </Link>

                <Card>
                    <CardHeader className="gap-3">
                        <div className="flex items-start gap-4">
                            <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-indigo-50 text-indigo-600">
                                <BookOpen className="h-6 w-6" aria-hidden="true" />
                            </span>

                            <div className="min-w-0 space-y-2">
                                <CardTitle className="text-xl leading-snug sm:text-2xl">
                                    {book.title}
                                </CardTitle>
                                <p className="text-sm text-gray-600">
                                    oleh {book.author}
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary">
                                {book.category ?? 'Tanpa kategori'}
                            </Badge>

                            <Badge variant={tersedia ? 'success' : 'destructive'}>
                                {tersedia ? (
                                    <CheckCircle2
                                        className="mr-1 h-3.5 w-3.5"
                                        aria-hidden="true"
                                    />
                                ) : (
                                    <XCircle
                                        className="mr-1 h-3.5 w-3.5"
                                        aria-hidden="true"
                                    />
                                )}
                                {tersedia ? 'Tersedia' : 'Habis'}
                            </Badge>
                        </div>
                    </CardHeader>

                    <CardContent className="space-y-6">
                        <dl className="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                            <DetailRow label="Judul" value={book.title} />
                            <DetailRow label="Pengarang" value={book.author} />
                            <DetailRow
                                label="Kategori"
                                value={book.category ?? 'Tanpa kategori'}
                            />
                            <DetailRow
                                label="Penerbit"
                                value={book.publisher ?? 'Tidak dicantumkan'}
                            />
                            <DetailRow
                                label="Tahun terbit"
                                value={
                                    book.publication_year === null
                                        ? 'Tidak dicantumkan'
                                        : String(book.publication_year)
                                }
                            />
                            <DetailRow
                                label="Lokasi rak"
                                value={
                                    book.shelf_location ??
                                    'Lokasi belum diatur'
                                }
                            />
                            <DetailRow
                                label="Jumlah eksemplar"
                                value={String(book.total_copies)}
                            />
                            <DetailRow
                                label="Stok tersedia"
                                value={
                                    book.available_copies === null
                                        ? tersedia
                                            ? 'Tersedia'
                                            : 'Habis'
                                        : `${book.available_copies} eksemplar`
                                }
                            />
                        </dl>

                        <div className="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                            Ingin meminjam buku ini? Silakan hubungi petugas
                            perpustakaan DPRD Kota Banjarmasin. Peminjaman
                            dicatat langsung oleh petugas.
                        </div>

                        <Link
                            href={route('catalog.index')}
                            className={cn(
                                buttonVariants({ variant: 'outline' }),
                                'w-full sm:w-auto',
                            )}
                        >
                            Telusuri buku lain
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </PublicLayout>
    );
}

function DetailRow({
    label,
    value,
}: {
    label: string;
    value: string;
}) {
    return (
        <div className="space-y-1">
            <dt className="text-xs font-medium uppercase tracking-wide text-gray-500">
                {label}
            </dt>
            <dd className="text-sm text-gray-900">{value}</dd>
        </div>
    );
}
