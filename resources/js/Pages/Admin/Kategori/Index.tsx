import InputError from '@/Components/InputError';
import AlertDialog from '@/Components/ui/alert-dialog';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import Dialog from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import Pagination from '@/Components/ui/pagination';
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

import type { Category, Paginated } from '@/types';

interface KategoriIndexProps {
    categories: Paginated<Category>;
    filters: {
        search: string;
    };
}

export default function KategoriIndex({
    categories,
    filters,
}: KategoriIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Category | null>(null);
    const [deleting, setDeleting] = useState<Category | null>(null);

    useEffect(() => {
        setSearch(filters.search);
    }, [filters.search]);

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();

        router.get(
            route('admin.categories.index'),
            { search },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (category: Category) => {
        setEditing(category);
        setFormOpen(true);
    };

    return (
        <AdminLayout title="Kategori">
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
                                placeholder="Cari nama kategori..."
                                className="w-full pl-9 sm:w-72"
                                aria-label="Cari kategori"
                            />
                        </div>

                        <Button type="submit" variant="outline">
                            Cari
                        </Button>
                    </form>

                    <Button onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        Tambah Kategori
                    </Button>
                </div>

                <Card>
                    <CardContent className="p-0">
                        {categories.data.length === 0 ? (
                            <p className="px-6 py-10 text-center text-sm text-gray-500">
                                {filters.search !== ''
                                    ? 'Tidak ada kategori yang cocok dengan pencarian.'
                                    : 'Belum ada kategori. Tambahkan kategori pertama Anda.'}
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama Kategori</TableHead>
                                        <TableHead>Jumlah Buku</TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {categories.data.map((category) => (
                                        <TableRow key={category.id}>
                                            <TableCell className="font-medium text-gray-900">
                                                {category.name}
                                            </TableCell>
                                            <TableCell>
                                                {category.books_count ?? 0}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            openEdit(category)
                                                        }
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                        Ubah
                                                    </Button>

                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        onClick={() =>
                                                            setDeleting(category)
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
                        Menampilkan {categories.data.length} dari{' '}
                        {categories.total} kategori
                    </p>

                    <Pagination links={categories.links} />
                </div>
            </div>

            <CategoryFormDialog
                show={formOpen}
                category={editing}
                onClose={() => setFormOpen(false)}
            />

            <DeleteCategoryDialog
                category={deleting}
                onClose={() => setDeleting(null)}
            />
        </AdminLayout>
    );
}

function CategoryFormDialog({
    show,
    category,
    onClose,
}: {
    show: boolean;
    category: Category | null;
    onClose: () => void;
}) {
    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm({
            name: '',
        });

    useEffect(() => {
        if (!show) {
            return;
        }

        clearErrors();
        reset();
        setData('name', category?.name ?? '');
    }, [show, category, clearErrors, reset, setData]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        };

        if (category) {
            put(route('admin.categories.update', category.id), options);
        } else {
            post(route('admin.categories.store'), options);
        }
    };

    return (
        <Dialog
            show={show}
            onClose={onClose}
            title={category ? 'Ubah Kategori' : 'Tambah Kategori'}
            description="Nama kategori harus unik."
            maxWidth="md"
            closeable={!processing}
        >
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="name">Nama Kategori</Label>
                    <Input
                        id="name"
                        className="mt-1"
                        value={data.name}
                        autoFocus
                        onChange={(e) => setData('name', e.target.value)}
                    />
                    <InputError message={errors.name} className="mt-2" />
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

function DeleteCategoryDialog({
    category,
    onClose,
}: {
    category: Category | null;
    onClose: () => void;
}) {
    const [processing, setProcessing] = useState(false);

    const confirm = () => {
        if (!category) {
            return;
        }

        setProcessing(true);

        router.delete(route('admin.categories.destroy', category.id), {
            preserveScroll: true,
            onFinish: () => setProcessing(false),
            onSuccess: () => onClose(),
        });
    };

    return (
        <AlertDialog
            show={category !== null}
            title="Hapus Kategori"
            description={
                <>
                    Yakin ingin menghapus kategori{' '}
                    <span className="font-medium text-gray-900">
                        {category?.name}
                    </span>
                    ? Kategori yang masih dipakai buku tidak dapat dihapus.
                </>
            }
            processing={processing}
            onConfirm={confirm}
            onClose={onClose}
        />
    );
}
