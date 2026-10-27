import InputError from '@/Components/InputError';
import Badge from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AdminLayout from '@/Layouts/AdminLayout';
import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { FormEventHandler } from 'react';

import type { ReturnFormItem, ReturnFormLoan } from '@/types';

interface PengembalianCreateProps {
    loan: ReturnFormLoan;
    items: ReturnFormItem[];
    defaultReturnDate: string;
}

interface ReturnItemForm {
    loan_item_id: string;
    quantity: string;
}

interface ReturnFormData {
    return_date: string;
    items: ReturnItemForm[];
}

/**
 * Form pencatatan pengembalian per judul (FR-RT-02, FR-RT-03).
 *
 * Jumlah maksimum dibatasi sisa yang belum dikembalikan pada UI; validasi
 * server tetap menentukan. Baris dengan jumlah 0 berarti tidak dikembalikan.
 */
export default function PengembalianCreate({
    loan,
    items,
    defaultReturnDate,
}: PengembalianCreateProps) {
    const { data, setData, post, processing, errors } = useForm<ReturnFormData>({
        return_date: defaultReturnDate,
        items: items.map((item) => ({
            loan_item_id: String(item.id),
            quantity: '0',
        })),
    });

    const setQuantity = (index: number, quantity: string) => {
        setData(
            'items',
            data.items.map((item, itemIndex) =>
                itemIndex === index ? { ...item, quantity } : item,
            ),
        );
    };

    const fillRemaining = () => {
        setData(
            'items',
            data.items.map((item, index) => ({
                ...item,
                quantity: String(items[index]?.remaining_quantity ?? 0),
            })),
        );
    };

    const resetAll = () => {
        setData(
            'items',
            data.items.map((item) => ({ ...item, quantity: '0' })),
        );
    };

    const totalKembali = data.items.reduce((total, item) => {
        const quantity = Number(item.quantity);

        return total + (Number.isFinite(quantity) && quantity > 0 ? quantity : 0);
    }, 0);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('admin.returns.store', loan.id), {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title="Catat Pengembalian">
            <form onSubmit={submit} className="space-y-6">
                <Link
                    href={route('admin.returns.index')}
                    className="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:underline"
                >
                    <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                    Kembali ke daftar pengembalian
                </Link>

                <Card>
                    <CardHeader className="flex flex-row items-start justify-between space-y-0">
                        <div className="space-y-1">
                            <CardTitle>Informasi Transaksi</CardTitle>
                            <p className="text-sm text-gray-500">
                                Nomor transaksi{' '}
                                <span className="font-medium text-gray-900">
                                    {loan.loan_number}
                                </span>
                            </p>
                        </div>

                        {loan.is_overdue && (
                            <Badge variant="destructive">Terlambat</Badge>
                        )}
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                            <DetailRow
                                label="Peminjam"
                                value={loan.borrower.name ?? '-'}
                            />
                            <DetailRow
                                label="Nomor identitas"
                                value={loan.borrower.identity_number ?? '-'}
                            />
                            <DetailRow
                                label="Tanggal peminjaman"
                                value={loan.loan_date ?? '-'}
                            />
                            <DetailRow
                                label="Tanggal jatuh tempo"
                                value={loan.due_date ?? '-'}
                            />

                            {loan.is_overdue && (
                                <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 sm:col-span-2">
                                    Transaksi ini melewati tanggal jatuh tempo.
                                    Penanda ini hanya informasi, tanpa denda.
                                </div>
                            )}
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Rincian Buku</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="max-w-xs">
                            <Label htmlFor="return_date">
                                Tanggal Pengembalian
                            </Label>
                            <Input
                                id="return_date"
                                type="date"
                                className="mt-1"
                                value={data.return_date}
                                onChange={(e) =>
                                    setData('return_date', e.target.value)
                                }
                            />
                            <InputError
                                message={errors.return_date}
                                className="mt-2"
                            />
                        </div>

                        {errors.items && (
                            <p className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                {errors.items}
                            </p>
                        )}

                        {items.length === 0 ? (
                            <p className="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                Semua buku pada transaksi ini sudah
                                dikembalikan.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Judul</TableHead>
                                        <TableHead className="text-right">
                                            Dipinjam
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Sudah Kembali
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Sisa
                                        </TableHead>
                                        <TableHead className="w-40">
                                            Jumlah Kembali
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {items.map((item, index) => {
                                        const itemError =
                                            errors[
                                                `items.${index}.quantity`
                                            ];

                                        return (
                                            <TableRow key={item.id}>
                                                <TableCell className="font-medium text-gray-900">
                                                    <span className="block">
                                                        {item.title ?? '-'}
                                                    </span>
                                                    <span className="block text-xs text-gray-500">
                                                        {item.author ?? '-'}
                                                    </span>
                                                    {itemError && (
                                                        <span className="mt-1 block text-xs text-red-600">
                                                            {itemError}
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.quantity}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.returned_quantity}
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {item.remaining_quantity}
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        id={`items-${index}-quantity`}
                                                        type="number"
                                                        inputMode="numeric"
                                                        min={0}
                                                        max={
                                                            item.remaining_quantity
                                                        }
                                                        aria-label={`Jumlah kembali ${item.title ?? ''}`}
                                                        value={
                                                            data.items[index]
                                                                ?.quantity ?? '0'
                                                        }
                                                        onChange={(e) =>
                                                            setQuantity(
                                                                index,
                                                                e.target.value,
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                <div className="flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-sm text-gray-500">
                        Total yang dikembalikan: {totalKembali} eksemplar
                    </p>

                    <div className="flex flex-wrap gap-3">
                        <Button
                            variant="outline"
                            onClick={fillRemaining}
                            disabled={processing || items.length === 0}
                        >
                            Isi Semua Sisa
                        </Button>

                        <Button
                            variant="outline"
                            onClick={resetAll}
                            disabled={processing}
                        >
                            Kosongkan
                        </Button>

                        <Button
                            type="submit"
                            disabled={processing || totalKembali === 0}
                        >
                            {processing
                                ? 'Menyimpan...'
                                : 'Simpan Pengembalian'}
                        </Button>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}

function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="space-y-1">
            <dt className="text-xs font-medium uppercase tracking-wide text-gray-500">
                {label}
            </dt>
            <dd className="text-sm text-gray-900">{value}</dd>
        </div>
    );
}
