import Badge from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
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
import { Link, router } from '@inertiajs/react';
import { RotateCcw, Search } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

import type { Paginated, ReturnableLoan } from '@/types';

interface PengembalianIndexProps {
    loans: Paginated<ReturnableLoan>;
    filters: {
        search: string;
    };
}

/**
 * Pencarian transaksi yang belum selesai untuk dicatat pengembaliannya
 * (FR-RT-01).
 */
export default function PengembalianIndex({
    loans,
    filters,
}: PengembalianIndexProps) {
    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        setSearch(filters.search);
    }, [filters.search]);

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();

        router.get(
            route('admin.returns.index'),
            { search },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <AdminLayout title="Pengembalian">
            <div className="space-y-4">
                <form onSubmit={submitSearch} className="flex gap-2">
                    <div className="relative">
                        <Search
                            className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                            aria-hidden="true"
                        />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari nomor transaksi atau nama peminjam..."
                            className="w-full pl-9 sm:w-80"
                            aria-label="Cari transaksi"
                        />
                    </div>

                    <Button type="submit" variant="outline">
                        Cari
                    </Button>
                </form>

                <p className="text-sm text-gray-500">
                    Hanya transaksi yang belum selesai yang dapat dicatat
                    pengembaliannya.
                </p>

                <Card>
                    <CardContent className="p-0">
                        {loans.data.length === 0 ? (
                            <p className="px-6 py-10 text-center text-sm text-gray-500">
                                {filters.search !== ''
                                    ? 'Tidak ada transaksi yang cocok dengan pencarian.'
                                    : 'Tidak ada transaksi yang menunggu pengembalian.'}
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
                                            Dipinjam
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Sisa
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
                                                {loan.loan_number}
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
                                            <TableCell className="text-right font-medium">
                                                {loan.sisa_eksemplar}
                                            </TableCell>
                                            <TableCell>
                                                {loan.is_overdue ? (
                                                    <Badge variant="destructive">
                                                        Terlambat
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="success">
                                                        Berjalan
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Link
                                                    href={route(
                                                        'admin.returns.create',
                                                        loan.id,
                                                    )}
                                                    className="inline-flex h-9 items-center gap-2 rounded-md border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                                                >
                                                    <RotateCcw className="h-4 w-4" />
                                                    Catat Pengembalian
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
