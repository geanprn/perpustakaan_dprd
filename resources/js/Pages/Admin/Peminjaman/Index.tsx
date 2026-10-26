import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
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
import Badge from '@/Components/ui/badge';
import AdminLayout from '@/Layouts/AdminLayout';
import { Link, router } from '@inertiajs/react';
import { Eye, Plus, Search } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

import type { LoanListItem, LoanStatusFilter, Paginated } from '@/types';

interface PeminjamanIndexProps {
    loans: Paginated<LoanListItem>;
    filters: {
        search: string;
        status: LoanStatusFilter;
    };
}

/**
 * Daftar transaksi peminjaman dengan filter status dan pencarian peminjam
 * (FR-LN-09).
 */
export default function PeminjamanIndex({
    loans,
    filters,
}: PeminjamanIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState<LoanStatusFilter>(filters.status);

    useEffect(() => {
        setSearch(filters.search);
    }, [filters.search]);

    useEffect(() => {
        setStatus(filters.status);
    }, [filters.status]);

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();

        router.get(
            route('admin.loans.index'),
            { search, status },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const changeStatus = (value: LoanStatusFilter) => {
        setStatus(value);

        router.get(
            route('admin.loans.index'),
            { search, status: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hasFilter = search !== '' || status !== '';

    return (
        <AdminLayout title="Peminjaman">
            <div className="space-y-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-col gap-2 sm:flex-row">
                        <form onSubmit={submitSearch} className="flex gap-2">
                            <div className="relative">
                                <Search
                                    className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                                    aria-hidden="true"
                                />
                                <Input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari nomor transaksi atau peminjam..."
                                    className="w-full pl-9 sm:w-72"
                                    aria-label="Cari peminjaman"
                                />
                            </div>

                            <Button type="submit" variant="outline">
                                Cari
                            </Button>
                        </form>

                        <Select
                            value={status}
                            onChange={(e) =>
                                changeStatus(
                                    e.target.value as LoanStatusFilter,
                                )
                            }
                            className="sm:w-44"
                            aria-label="Filter status peminjaman"
                        >
                            <option value="">Semua status</option>
                            <option value="berjalan">Berjalan</option>
                            <option value="terlambat">Terlambat</option>
                            <option value="selesai">Selesai</option>
                        </Select>
                    </div>

                    <Link
                        href={route('admin.loans.create')}
                        className="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-gray-900 px-4 text-sm font-medium text-white transition-colors hover:bg-gray-800"
                    >
                        <Plus className="h-4 w-4" aria-hidden="true" />
                        Buat Peminjaman
                    </Link>
                </div>

                <Card>
                    <CardContent className="p-0">
                        {loans.data.length === 0 ? (
                            <p className="px-6 py-10 text-center text-sm text-gray-500">
                                {hasFilter
                                    ? 'Tidak ada transaksi yang cocok dengan filter.'
                                    : 'Belum ada transaksi peminjaman. Catat peminjaman pertama Anda.'}
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nomor Transaksi</TableHead>
                                        <TableHead>Peminjam</TableHead>
                                        <TableHead>Tanggal Pinjam</TableHead>
                                        <TableHead>Jatuh Tempo</TableHead>
                                        <TableHead className="text-right">
                                            Eksemplar
                                        </TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {loans.data.map((loan) => (
                                        <TableRow key={loan.id}>
                                            <TableCell className="font-medium text-gray-900">
                                                <Link
                                                    href={route(
                                                        'admin.loans.show',
                                                        loan.id,
                                                    )}
                                                    className="hover:text-indigo-700 hover:underline"
                                                >
                                                    {loan.loan_number}
                                                </Link>
                                            </TableCell>
                                            <TableCell>
                                                <span className="block text-gray-900">
                                                    {loan.borrower.name ?? '-'}
                                                </span>
                                                <span className="block text-xs text-gray-500">
                                                    {loan.borrower
                                                        .identity_number ?? '-'}
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                {loan.loan_date ?? '-'}
                                            </TableCell>
                                            <TableCell>
                                                {loan.due_date ?? '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {loan.jumlah_eksemplar} dari{' '}
                                                {loan.jumlah_judul} judul
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap items-center gap-1">
                                                    <Badge
                                                        variant={
                                                            loan.status ===
                                                            'selesai'
                                                                ? 'secondary'
                                                                : 'success'
                                                        }
                                                    >
                                                        {loan.status ===
                                                        'selesai'
                                                            ? 'Selesai'
                                                            : 'Berjalan'}
                                                    </Badge>

                                                    {loan.is_overdue && (
                                                        <Badge variant="destructive">
                                                            Terlambat
                                                        </Badge>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Link
                                                    href={route(
                                                        'admin.loans.show',
                                                        loan.id,
                                                    )}
                                                    className="inline-flex h-9 items-center gap-2 rounded-md border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                                                >
                                                    <Eye className="h-4 w-4" />
                                                    Detail
                                                </Link>
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
                        Menampilkan {loans.data.length} dari {loans.total}{' '}
                        transaksi
                    </p>

                    <Pagination links={loans.links} />
                </div>
            </div>
        </AdminLayout>
    );
}
