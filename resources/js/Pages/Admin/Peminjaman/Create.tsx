import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import Dialog from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select } from '@/Components/ui/select';
import AdminLayout from '@/Layouts/AdminLayout';
import { cn } from '@/lib/utils';
import {
    Combobox,
    ComboboxButton,
    ComboboxInput,
    ComboboxOption,
    ComboboxOptions,
} from '@headlessui/react';
import { Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    ChevronsUpDown,
    Plus,
    Trash2,
    UserPlus,
} from 'lucide-react';
import { FormEventHandler, useEffect, useMemo, useState } from 'react';

import type { BorrowerOption, LoanFormBook } from '@/types';

interface PeminjamanCreateProps {
    borrowers: BorrowerOption[];
    books: LoanFormBook[];
    defaultLoanDate: string;
    selectedBorrowerId: number | null;
}

interface LoanItemForm {
    book_id: string;
    quantity: string;
}

interface LoanFormData {
    borrower_id: string;
    loan_date: string;
    due_date: string;
    notes: string;
    items: LoanItemForm[];
}

/**
 * Form pencatatan peminjaman baru (FR-LN-01 sampai FR-LN-08).
 *
 * Peminjam dapat dicari/dipilih dari data yang ada atau ditambahkan baru
 * lewat dialog memakai CreateBorrowerAction tanpa meninggalkan form
 * (FR-PJ-05). Batas jumlah pada UI hanya saran; validasi server tetap
 * menentukan.
 */
export default function PeminjamanCreate({
    borrowers,
    books,
    defaultLoanDate,
    selectedBorrowerId,
}: PeminjamanCreateProps) {
    const [borrowerQuery, setBorrowerQuery] = useState('');
    const [borrowerDialogOpen, setBorrowerDialogOpen] = useState(false);

    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
    } = useForm<LoanFormData>({
        borrower_id: selectedBorrowerId !== null ? String(selectedBorrowerId) : '',
        loan_date: defaultLoanDate,
        due_date: '',
        notes: '',
        items: [{ book_id: '', quantity: '1' }],
    });

    useEffect(() => {
        if (selectedBorrowerId === null) {
            return;
        }

        setData('borrower_id', String(selectedBorrowerId));
    }, [selectedBorrowerId, setData]);

    const selectedBorrower = useMemo(() => {
        const id = Number(data.borrower_id);

        return borrowers.find((borrower) => borrower.id === id) ?? null;
    }, [borrowers, data.borrower_id]);

    const filteredBorrowers = useMemo(() => {
        const query = borrowerQuery.trim().toLowerCase();

        if (query === '') {
            return borrowers;
        }

        return borrowers.filter(
            (borrower) =>
                borrower.name.toLowerCase().includes(query) ||
                borrower.identity_number.toLowerCase().includes(query),
        );
    }, [borrowers, borrowerQuery]);

    const bookMap = useMemo(() => {
        const map = new Map<number, LoanFormBook>();

        books.forEach((book) => map.set(book.id, book));

        return map;
    }, [books]);

    const updateItem = (index: number, patch: Partial<LoanItemForm>) => {
        const items = data.items.map((item, itemIndex) =>
            itemIndex === index ? { ...item, ...patch } : item,
        );

        setData('items', items);
    };

    const addItem = () => {
        setData('items', [...data.items, { book_id: '', quantity: '1' }]);
    };

    const removeItem = (index: number) => {
        setData(
            'items',
            data.items.filter((_, itemIndex) => itemIndex !== index),
        );
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('admin.loans.store'), {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title="Buat Peminjaman">
            <form onSubmit={submit} className="space-y-6">
                <Link
                    href={route('admin.loans.index')}
                    className="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:underline"
                >
                    <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                    Kembali ke daftar peminjaman
                </Link>

                <Card>
                    <CardHeader>
                        <CardTitle>Data Peminjaman</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <Label htmlFor="borrower">Peminjam</Label>

                            <div className="mt-1 flex flex-col gap-2 sm:flex-row">
                                <Combobox
                                    value={selectedBorrower}
                                    onChange={(borrower: BorrowerOption | null) =>
                                        setData(
                                            'borrower_id',
                                            borrower ? String(borrower.id) : '',
                                        )
                                    }
                                    onClose={() => setBorrowerQuery('')}
                                >
                                    <div className="relative flex-1">
                                        <ComboboxInput
                                            id="borrower"
                                            className="flex h-10 w-full rounded-md border border-gray-300 bg-white pl-3 pr-9 text-sm shadow-sm placeholder:text-gray-400 focus-visible:border-indigo-500 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500"
                                            displayValue={(borrower: BorrowerOption | null) =>
                                                borrower
                                                    ? `${borrower.name} — ${borrower.identity_number}`
                                                    : ''
                                            }
                                            placeholder="Cari nama atau nomor identitas..."
                                            onChange={(event) =>
                                                setBorrowerQuery(event.target.value)
                                            }
                                        />

                                        <ComboboxButton
                                            className="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400"
                                            aria-label="Buka daftar peminjam"
                                        >
                                            <ChevronsUpDown className="h-4 w-4" />
                                        </ComboboxButton>

                                        <ComboboxOptions className="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg empty:invisible">
                                            {filteredBorrowers.length === 0 ? (
                                                <p className="px-3 py-2 text-sm text-gray-500">
                                                    Peminjam tidak ditemukan.
                                                </p>
                                            ) : (
                                                filteredBorrowers.map((borrower) => (
                                                    <ComboboxOption
                                                        key={borrower.id}
                                                        value={borrower}
                                                        className="flex cursor-pointer items-center justify-between gap-3 px-3 py-2 text-sm text-gray-700 data-[focus]:bg-indigo-50 data-[focus]:text-indigo-700"
                                                    >
                                                        {({ selected }) => (
                                                            <>
                                                                <span>
                                                                    <span className="block font-medium">
                                                                        {
                                                                            borrower.name
                                                                        }
                                                                    </span>
                                                                    <span className="block text-xs text-gray-500">
                                                                        {
                                                                            borrower.identity_number
                                                                        }
                                                                    </span>
                                                                </span>

                                                                <Check
                                                                    className={cn(
                                                                        'h-4 w-4 text-indigo-600',
                                                                        selected
                                                                            ? 'visible'
                                                                            : 'invisible',
                                                                    )}
                                                                />
                                                            </>
                                                        )}
                                                    </ComboboxOption>
                                                ))
                                            )}
                                        </ComboboxOptions>
                                    </div>
                                </Combobox>

                                <Button
                                    variant="outline"
                                    onClick={() => setBorrowerDialogOpen(true)}
                                >
                                    <UserPlus className="h-4 w-4" />
                                    Tambah Peminjam Baru
                                </Button>
                            </div>

                            <InputError
                                message={errors.borrower_id}
                                className="mt-2"
                            />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <Label htmlFor="loan_date">
                                    Tanggal Peminjaman
                                </Label>
                                <Input
                                    id="loan_date"
                                    type="date"
                                    className="mt-1"
                                    value={data.loan_date}
                                    onChange={(e) =>
                                        setData('loan_date', e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.loan_date}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <Label htmlFor="due_date">
                                    Tanggal Jatuh Tempo (opsional)
                                </Label>
                                <Input
                                    id="due_date"
                                    type="date"
                                    className="mt-1"
                                    value={data.due_date}
                                    onChange={(e) =>
                                        setData('due_date', e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.due_date}
                                    className="mt-2"
                                />
                            </div>

                            <div className="sm:col-span-2">
                                <Label htmlFor="notes">
                                    Catatan (opsional)
                                </Label>
                                <textarea
                                    id="notes"
                                    rows={3}
                                    className="mt-1 flex w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus-visible:border-indigo-500 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500"
                                    value={data.notes}
                                    onChange={(e) =>
                                        setData('notes', e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.notes}
                                    className="mt-2"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0">
                        <CardTitle>Daftar Buku</CardTitle>

                        <Button
                            variant="outline"
                            onClick={addItem}
                            disabled={books.length === 0}
                        >
                            <Plus className="h-4 w-4" />
                            Tambah Baris
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {books.length === 0 ? (
                            <p className="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                Tidak ada buku dengan stok tersedia. Periksa
                                kembali koleksi perpustakaan.
                            </p>
                        ) : null}

                        {errors.items && (
                            <p className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                {errors.items}
                            </p>
                        )}

                        {data.items.map((item, index) => {
                            const selectedBook =
                                item.book_id !== ''
                                    ? bookMap.get(Number(item.book_id)) ?? null
                                    : null;

                            return (
                                <div
                                    key={index}
                                    className="grid gap-3 rounded-md border border-gray-200 p-3 sm:grid-cols-[1fr_10rem_auto] sm:items-start"
                                >
                                    <div>
                                        <Label
                                            htmlFor={`items-${index}-book`}
                                        >
                                            Buku
                                        </Label>
                                        <Select
                                            id={`items-${index}-book`}
                                            className="mt-1"
                                            value={item.book_id}
                                            onChange={(e) =>
                                                updateItem(index, {
                                                    book_id: e.target.value,
                                                })
                                            }
                                        >
                                            <option value="">
                                                Pilih buku
                                            </option>
                                            {books.map((book) => (
                                                <option
                                                    key={book.id}
                                                    value={String(book.id)}
                                                >
                                                    {book.title} — stok:{' '}
                                                    {book.available_copies}
                                                </option>
                                            ))}
                                        </Select>
                                        <InputError
                                            message={
                                                errors[
                                                    `items.${index}.book_id`
                                                ]
                                            }
                                            className="mt-2"
                                        />
                                    </div>

                                    <div>
                                        <Label
                                            htmlFor={`items-${index}-quantity`}
                                        >
                                            Jumlah
                                        </Label>
                                        <Input
                                            id={`items-${index}-quantity`}
                                            type="number"
                                            inputMode="numeric"
                                            min={1}
                                            max={
                                                selectedBook?.available_copies ??
                                                undefined
                                            }
                                            className="mt-1"
                                            value={item.quantity}
                                            onChange={(e) =>
                                                updateItem(index, {
                                                    quantity: e.target.value,
                                                })
                                            }
                                        />
                                        <InputError
                                            message={
                                                errors[
                                                    `items.${index}.quantity`
                                                ]
                                            }
                                            className="mt-2"
                                        />

                                        {selectedBook && (
                                            <p className="mt-2 text-xs text-gray-500">
                                                Stok tersedia:{' '}
                                                {selectedBook.available_copies}{' '}
                                                eksemplar
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex items-end sm:pt-7">
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => removeItem(index)}
                                            disabled={data.items.length === 1}
                                            aria-label={`Hapus baris ${index + 1}`}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                            Hapus
                                        </Button>
                                    </div>
                                </div>
                            );
                        })}
                    </CardContent>
                </Card>

                <div className="flex justify-end gap-3">
                    <Button
                        variant="outline"
                        onClick={() => reset()}
                        disabled={processing}
                    >
                        Reset
                    </Button>

                    <Button
                        type="submit"
                        disabled={processing || data.borrower_id === ''}
                    >
                        {processing ? 'Menyimpan...' : 'Simpan Peminjaman'}
                    </Button>
                </div>
            </form>

            <NewBorrowerDialog
                show={borrowerDialogOpen}
                onClose={() => setBorrowerDialogOpen(false)}
            />
        </AdminLayout>
    );
}

interface NewBorrowerFormData {
    name: string;
    identity_number: string;
    institution: string;
    phone: string;
    address: string;
}

const emptyBorrowerForm: NewBorrowerFormData = {
    name: '',
    identity_number: '',
    institution: '',
    phone: '',
    address: '',
};

/**
 * Dialog peminjam baru yang memakai ulang alur CreateBorrowerAction
 * (FR-PJ-05) dan kembali ke form peminjaman dengan peminjam terpilih.
 */
function NewBorrowerDialog({
    show,
    onClose,
}: {
    show: boolean;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm<NewBorrowerFormData>(emptyBorrowerForm);

    useEffect(() => {
        if (!show) {
            return;
        }

        clearErrors();
        reset();
    }, [show, clearErrors, reset]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('admin.loans.borrowers.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <Dialog
            show={show}
            onClose={onClose}
            title="Tambah Peminjam Baru"
            description="Nomor identitas harus unik. Peminjam baru akan langsung terpilih pada form peminjaman."
            maxWidth="2xl"
            closeable={!processing}
        >
            <form onSubmit={submit} className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <Label htmlFor="new-borrower-name">Nama</Label>
                        <Input
                            id="new-borrower-name"
                            className="mt-1"
                            value={data.name}
                            autoFocus
                            onChange={(e) =>
                                setData('name', e.target.value)
                            }
                        />
                        <InputError message={errors.name} className="mt-2" />
                    </div>

                    <div>
                        <Label htmlFor="new-borrower-identity">
                            Nomor Identitas (NIP/NIK)
                        </Label>
                        <Input
                            id="new-borrower-identity"
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
                        <Label htmlFor="new-borrower-institution">
                            Instansi/Unit
                        </Label>
                        <Input
                            id="new-borrower-institution"
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
                        <Label htmlFor="new-borrower-phone">
                            Nomor Telepon
                        </Label>
                        <Input
                            id="new-borrower-phone"
                            className="mt-1"
                            inputMode="tel"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                        />
                        <InputError message={errors.phone} className="mt-2" />
                    </div>

                    <div className="sm:col-span-2">
                        <Label htmlFor="new-borrower-address">Alamat</Label>
                        <textarea
                            id="new-borrower-address"
                            rows={3}
                            className="mt-1 flex w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus-visible:border-indigo-500 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500"
                            value={data.address}
                            onChange={(e) =>
                                setData('address', e.target.value)
                            }
                        />
                        <InputError
                            message={errors.address}
                            className="mt-2"
                        />
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
                        {processing ? 'Menyimpan...' : 'Simpan Peminjam'}
                    </Button>
                </div>
            </form>
        </Dialog>
    );
}
