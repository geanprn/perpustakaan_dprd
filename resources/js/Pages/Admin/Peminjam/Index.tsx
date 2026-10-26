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
import { Link, router, useForm } from '@inertiajs/react';
import { Eye, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

import type { Borrower, Paginated } from '@/types';

interface PeminjamIndexProps {
    borrowers: Paginated<Borrower>;
    filters: {
        search: string;
    };
}

interface BorrowerFormData {
    name: string;
    identity_number: string;
    institution: string;
    phone: string;
    address: string;
}

const emptyForm: BorrowerFormData = {
    name: '',
    identity_number: '',
    institution: '',
    phone: '',
    address: '',
};

export default function PeminjamIndex({
    borrowers,
    filters,
}: PeminjamIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Borrower | null>(null);
    const [deleting, setDeleting] = useState<Borrower | null>(null);

    useEffect(() => {
        setSearch(filters.search);
    }, [filters.search]);

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();

        router.get(
            route('admin.borrowers.index'),
            { search },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <AdminLayout title="Peminjam">
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
                                placeholder="Cari nama atau nomor identitas..."
                                className="w-full pl-9 sm:w-72"
                                aria-label="Cari peminjam"
                            />
                        </div>

                        <Button type="submit" variant="outline">
                            Cari
                        </Button>
                    </form>

                    <Button
                        onClick={() => {
                            setEditing(null);
                            setFormOpen(true);
                        }}
                    >
                        <Plus className="h-4 w-4" />
                        Tambah Peminjam
                    </Button>
                </div>

                <Card>
                    <CardContent className="p-0">
                        {borrowers.data.length === 0 ? (
                            <p className="px-6 py-10 text-center text-sm text-gray-500">
                                {filters.search !== ''
                                    ? 'Tidak ada peminjam yang cocok dengan pencarian.'
                                    : 'Belum ada peminjam. Tambahkan peminjam pertama Anda.'}
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Nomor Identitas</TableHead>
                                        <TableHead>Instansi/Unit</TableHead>
                                        <TableHead>Telepon</TableHead>
                                        <TableHead className="text-right">
                                            Transaksi
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {borrowers.data.map((borrower) => (
                                        <TableRow key={borrower.id}>
                                            <TableCell className="font-medium text-gray-900">
                                                <Link
                                                    href={route(
                                                        'admin.borrowers.show',
                                                        borrower.id,
                                                    )}
                                                    className="hover:text-indigo-700 hover:underline"
                                                >
                                                    {borrower.name}
                                                </Link>
                                            </TableCell>
                                            <TableCell>
                                                {borrower.identity_number}
                                            </TableCell>
                                            <TableCell>
                                                {borrower.institution ?? '-'}
                                            </TableCell>
                                            <TableCell>
                                                {borrower.phone ?? '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {borrower.loans_count ?? 0}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Link
                                                        href={route(
                                                            'admin.borrowers.show',
                                                            borrower.id,
                                                        )}
                                                        className="inline-flex h-9 items-center gap-2 rounded-md border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                        Detail
                                                    </Link>

                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => {
                                                            setEditing(borrower);
                                                            setFormOpen(true);
                                                        }}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                        Ubah
                                                    </Button>

                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        onClick={() =>
                                                            setDeleting(borrower)
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
                        Menampilkan {borrowers.data.length} dari{' '}
                        {borrowers.total} peminjam
                    </p>

                    <Pagination links={borrowers.links} />
                </div>
            </div>

            <BorrowerFormDialog
                show={formOpen}
                borrower={editing}
                onClose={() => setFormOpen(false)}
            />

            <DeleteBorrowerDialog
                borrower={deleting}
                onClose={() => setDeleting(null)}
            />
        </AdminLayout>
    );
}

function BorrowerFormDialog({
    show,
    borrower,
    onClose,
}: {
    show: boolean;
    borrower: Borrower | null;
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
    } = useForm<BorrowerFormData>(emptyForm);

    useEffect(() => {
        if (!show) {
            return;
        }

        clearErrors();
        reset();

        if (borrower) {
            setData({
                name: borrower.name,
                identity_number: borrower.identity_number,
                institution: borrower.institution ?? '',
                phone: borrower.phone ?? '',
                address: borrower.address ?? '',
            });
        }
    }, [show, borrower, clearErrors, reset, setData]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        };

        if (borrower) {
            put(route('admin.borrowers.update', borrower.id), options);
        } else {
            post(route('admin.borrowers.store'), options);
        }
    };

    return (
        <Dialog
            show={show}
            onClose={onClose}
            title={borrower ? 'Ubah Peminjam' : 'Tambah Peminjam'}
            description="Isi data diri peminjam. Nomor identitas harus unik."
            maxWidth="2xl"
            closeable={!processing}
        >
            <form onSubmit={submit} className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <Label htmlFor="name">Nama</Label>
                        <Input
                            id="name"
                            className="mt-1"
                            value={data.name}
                            autoFocus
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors.name} className="mt-2" />
                    </div>

                    <div>
                        <Label htmlFor="identity_number">
                            Nomor Identitas (NIP/NIK)
                        </Label>
                        <Input
                            id="identity_number"
                            className="mt-1"
                            value={data.identity_number}
                            onChange={(e) =>
                                setData('identity_number', e.target.value)
                            }
                        />
                        <InputError
                            message={errors.identity_number}
                            className="mt-2"
                        />
                    </div>

                    <div>
                        <Label htmlFor="institution">Instansi/Unit</Label>
                        <Input
                            id="institution"
                            className="mt-1"
                            value={data.institution}
                            onChange={(e) =>
                                setData('institution', e.target.value)
                            }
                        />
                        <InputError
                            message={errors.institution}
                            className="mt-2"
                        />
                    </div>

                    <div>
                        <Label htmlFor="phone">Nomor Telepon</Label>
                        <Input
                            id="phone"
                            className="mt-1"
                            inputMode="tel"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                        />
                        <InputError message={errors.phone} className="mt-2" />
                    </div>

                    <div className="sm:col-span-2">
                        <Label htmlFor="address">Alamat</Label>
                        <textarea
                            id="address"
                            rows={3}
                            className="mt-1 flex w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus-visible:border-indigo-500 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                        />
                        <InputError message={errors.address} className="mt-2" />
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

function DeleteBorrowerDialog({
    borrower,
    onClose,
}: {
    borrower: Borrower | null;
    onClose: () => void;
}) {
    const [processing, setProcessing] = useState(false);

    const confirm = () => {
        if (!borrower) {
            return;
        }

        setProcessing(true);

        router.delete(route('admin.borrowers.destroy', borrower.id), {
            preserveScroll: true,
            onFinish: () => setProcessing(false),
            onSuccess: () => onClose(),
        });
    };

    return (
        <AlertDialog
            show={borrower !== null}
            title="Hapus Peminjam"
            description={
                <>
                    Yakin ingin menghapus peminjam{' '}
                    <span className="font-medium text-gray-900">
                        {borrower?.name}
                    </span>
                    ? Peminjam yang memiliki riwayat peminjaman tidak dapat
                    dihapus.
                </>
            }
            processing={processing}
            onConfirm={confirm}
            onClose={onClose}
        />
    );
}
