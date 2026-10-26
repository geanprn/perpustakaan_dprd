import InputError from '@/Components/InputError';
import AlertDialog from '@/Components/ui/alert-dialog';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import Dialog from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import Pagination from '@/Components/ui/pagination';
import { Select } from '@/Components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AdminLayout from '@/Layouts/AdminLayout';
import { router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

import type { Book, Category, Paginated } from '@/types';

interface BukuIndexProps {
    books: Paginated<Book>;
    categories: Category[];
    filters: {
        search: string;
    };
}

interface BookFormData {
    title: string;
    author: string;
    category_id: string;
    publisher: string;
    publication_year: string;
    shelf_location: string;
    total_copies: string;
}

const emptyBookForm: BookFormData = {
    title: '',
    author: '',
    category_id: '',
    publisher: '',
    publication_year: '',
    shelf_location: '',
    total_copies: '1',
};

export default function BukuIndex({
    books,
    categories,
    filters,
}: BukuIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Book | null>(null);
    const [deleting, setDeleting] = useState<Book | null>(null);

    useEffect(() => {
        setSearch(filters.search);
    }, [filters.search]);

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();

        router.get(
            route('admin.books.index'),
            { search },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (book: Book) => {
        setEditing(book);
        setFormOpen(true);
    };

    return (
        <AdminLayout title="Buku">
            <div className="space-y-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <form onSubmit={submitSearch} className="flex gap-2">
                        <div className="relative">
                            <Search
                                className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                                aria-hidden="true"
                            />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari judul atau pengarang..."
                                className="w-full pl-9 sm:w-72"
                                aria-label="Cari buku"
                            />
                        </div>

                        <Button type="submit" variant="outline">
                            Cari
                        </Button>
                    </form>

                    <Button onClick={openCreate} disabled={categories.length === 0}>
                        <Plus className="h-4 w-4" />
                        Tambah Buku
                    </Button>
                </div>

                {categories.length === 0 && (
                    <p className="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Belum ada kategori. Tambahkan kategori terlebih dahulu
                        sebelum menambah buku.
                    </p>
                )}

                <Card>
                    <CardContent className="p-0">
                        {books.data.length === 0 ? (
                            <p className="px-6 py-10 text-center text-sm text-gray-500">
                                {filters.search !== ''
                                    ? 'Tidak ada buku yang cocok dengan pencarian.'
                                    : 'Belum ada buku. Tambahkan buku pertama Anda.'}
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Judul</TableHead>
                                        <TableHead>Pengarang</TableHead>
                                        <TableHead>Kategori</TableHead>
                                        <TableHead>Lokasi Rak</TableHead>
                                        <TableHead className="text-right">
                                            Eksemplar
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Stok Tersedia
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {books.data.map((book) => (
                                        <TableRow key={book.id}>
                                            <TableCell className="font-medium text-gray-900">
                                                {book.title}
                                            </TableCell>
                                            <TableCell>{book.author}</TableCell>
                                            <TableCell>
                                                {book.category?.name ?? '-'}
                                            </TableCell>
                                            <TableCell>
                                                {book.shelf_location ?? '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {book.total_copies}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {book.available_copies}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            openEdit(book)
                                                        }
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                        Ubah
                                                    </Button>

                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        onClick={() =>
                                                            setDeleting(book)
                                                        }
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                        Hapus
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                <div className="flex flex-col items-center gap-3">
                    <p className="text-sm text-gray-500">
                        Menampilkan {books.data.length} dari {books.total} buku
                    </p>

                    <Pagination links={books.links} />
                </div>
            </div>

            <BookFormDialog
                show={formOpen}
                book={editing}
                categories={categories}
                onClose={() => setFormOpen(false)}
            />

            <DeleteBookDialog book={deleting} onClose={() => setDeleting(null)} />
        </AdminLayout>
    );
}

function BookFormDialog({
    show,
    book,
    categories,
    onClose,
}: {
    show: boolean;
    book: Book | null;
    categories: Category[];
    onClose: () => void;
}) {
    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        reset,
        clearErrors,
    } = useForm<BookFormData>(emptyBookForm);

    useEffect(() => {
        if (!show) {
            return;
        }

        clearErrors();
        reset();

        if (book) {
            setData({
                title: book.title,
                author: book.author,
                category_id: String(book.category_id),
                publisher: book.publisher ?? '',
                publication_year:
                    book.publication_year !== null
                        ? String(book.publication_year)
                        : '',
                shelf_location: book.shelf_location ?? '',
                total_copies: String(book.total_copies),
            });
        }
    }, [show, book, clearErrors, reset, setData]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        };

        if (book) {
            put(route('admin.books.update', book.id), options);
        } else {
            post(route('admin.books.store'), options);
        }
    };

    return (
        <Dialog
            show={show}
            onClose={onClose}
            title={book ? 'Ubah Buku' : 'Tambah Buku'}
            description={
                book
                    ? 'Mengubah jumlah eksemplar akan menyesuaikan stok tersedia.'
                    : 'Stok tersedia otomatis sama dengan jumlah eksemplar.'
            }
            maxWidth="2xl"
            closeable={!processing}
        >
            <form onSubmit={submit} className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="sm:col-span-2">
                        <Label htmlFor="title">Judul</Label>
                        <Input
                            id="title"
                            className="mt-1"
                            value={data.title}
                            autoFocus
                            onChange={(e) => setData('title', e.target.value)}
                        />
                        <InputError message={errors.title} className="mt-2" />
                    </div>

                    <div>
                        <Label htmlFor="author">Pengarang</Label>
                        <Input
                            id="author"
                            className="mt-1"
                            value={data.author}
                            onChange={(e) => setData('author', e.target.value)}
                        />
                        <InputError message={errors.author} className="mt-2" />
                    </div>

                    <div>
                        <Label htmlFor="category_id">Kategori</Label>
                        <Select
                            id="category_id"
                            className="mt-1"
                            value={data.category_id}
                            onChange={(e) =>
                                setData('category_id', e.target.value)
                            }
                        >
                            <option value="">Pilih kategori</option>
                            {categories.map((category) => (
                                <option
                                    key={category.id}
                                    value={String(category.id)}
                                >
                                    {category.name}
                                </option>
                            ))}
                        </Select>
                        <InputError
                            message={errors.category_id}
                            className="mt-2"
                        />
                    </div>

                    <div>
                        <Label htmlFor="publisher">Penerbit</Label>
                        <Input
                            id="publisher"
                            className="mt-1"
                            value={data.publisher}
                            onChange={(e) =>
                                setData('publisher', e.target.value)
                            }
                        />
                        <InputError message={errors.publisher} className="mt-2" />
                    </div>

                    <div>
                        <Label htmlFor="publication_year">Tahun Terbit</Label>
                        <Input
                            id="publication_year"
                            type="number"
                            inputMode="numeric"
                            className="mt-1"
                            value={data.publication_year}
                            onChange={(e) =>
                                setData('publication_year', e.target.value)
                            }
                        />
                        <InputError
                            message={errors.publication_year}
                            className="mt-2"
                        />
                    </div>

                    <div>
                        <Label htmlFor="shelf_location">Lokasi Rak</Label>
                        <Input
                            id="shelf_location"
                            className="mt-1"
                            value={data.shelf_location}
                            onChange={(e) =>
                                setData('shelf_location', e.target.value)
                            }
                        />
                        <InputError
                            message={errors.shelf_location}
                            className="mt-2"
                        />
                    </div>

                    <div>
                        <Label htmlFor="total_copies">Jumlah Eksemplar</Label>
                        <Input
                            id="total_copies"
                            type="number"
                            inputMode="numeric"
                            min={book ? 0 : 1}
                            className="mt-1"
                            value={data.total_copies}
                            onChange={(e) =>
                                setData('total_copies', e.target.value)
                            }
                        />
                        <InputError
                            message={errors.total_copies}
                            className="mt-2"
                        />

                        {book && (
                            <p className="mt-2 text-xs text-gray-500">
                                Sedang dipinjam:{' '}
                                {book.total_copies - book.available_copies}{' '}
                                eksemplar. Jumlah eksemplar tidak boleh kurang
                                dari angka ini.
                            </p>
                        )}
                    </div>
                </div>

                <div className="flex justify-end gap-3 pt-2">
                    <Button
                        variant="outline"
                        onClick={onClose}
                        disabled={processing}
                    >
                        Batal
                    </Button>

                    <Button type="submit" disabled={processing}>
                        {processing ? 'Menyimpan...' : 'Simpan'}
                    </Button>
                </div>
            </form>
        </Dialog>
    );
}

function DeleteBookDialog({
    book,
    onClose,
}: {
    book: Book | null;
    onClose: () => void;
}) {
    const [processing, setProcessing] = useState(false);

    const confirm = () => {
        if (!book) {
            return;
        }

        setProcessing(true);

        router.delete(route('admin.books.destroy', book.id), {
            preserveScroll: true,
            onFinish: () => setProcessing(false),
            onSuccess: () => onClose(),
        });
    };

    return (
        <AlertDialog
            show={book !== null}
            title="Hapus Buku"
            description={
                <>
                    Yakin ingin menghapus buku{' '}
                    <span className="font-medium text-gray-900">
                        {book?.title}
                    </span>
                    ? Buku yang memiliki riwayat peminjaman tidak dapat dihapus.
                </>
            }
            processing={processing}
            onConfirm={confirm}
            onClose={onClose}
        />
    );
}
