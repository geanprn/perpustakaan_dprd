import Badge from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AdminLayout from '@/Layouts/AdminLayout';
import { Link } from '@inertiajs/react';
import { ArrowLeft, RotateCcw } from 'lucide-react';

import type { LoanDetail, LoanDetailItem } from '@/types';

interface PeminjamanShowProps {
    loan: LoanDetail;
    items: LoanDetailItem[];
}

/**
 * Detail transaksi peminjaman (FR-LN-08).
 *
 * Riwayat pengembalian tampil kosong sampai pencatatan pengembalian
 * tersedia (Tahap 7).
 */
export default function PeminjamanShow({ loan, items }: PeminjamanShowProps) {
    const returns = items.flatMap((item) =>
        item.returns.map((entry) => ({ ...entry, title: item.title })),
    );

    return (
        <AdminLayout title="Detail Peminjaman">
            <div className="space-y-6">
                <Link
                    href={route('admin.loans.index')}
                    className="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:underline"
                >
                    <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                    Kembali ke daftar peminjaman
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

                        <div className="flex flex-wrap items-center gap-1">
                            <Badge
                                variant={
                                    loan.status === 'selesai'
                                        ? 'secondary'
                                        : 'success'
                                }
                            >
                                {loan.status === 'selesai'
                                    ? 'Selesai'
                                    : 'Berjalan'}
                            </Badge>

                            {loan.is_overdue && (
                                <Badge variant="destructive">Terlambat</Badge>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                            <DetailRow
                                label="Tanggal peminjaman"
                                value={loan.loan_date ?? '-'}
                            />
                            <DetailRow
                                label="Tanggal jatuh tempo"
                                value={loan.due_date ?? '-'}
                            />
                            <DetailRow
                                label="Petugas pencatat"
                                value={loan.petugas ?? '-'}
                            />
                            <DetailRow
                                label="Catatan"
                                value={loan.notes ?? '-'}
                            />

                            {loan.is_overdue && (
                                <div className="sm:col-span-2 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                    Transaksi ini melewati tanggal jatuh
                                    tempo. Penanda ini hanya informasi, tanpa
                                    denda.
                                </div>
                            )}
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Data Peminjam</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                            <DetailRow
                                label="Nama"
                                value={loan.borrower.name ?? '-'}
                            />
                            <DetailRow
                                label="Nomor identitas"
                                value={loan.borrower.identity_number ?? '-'}
                            />
                            <DetailRow
                                label="Instansi/Unit"
                                value={loan.borrower.institution ?? '-'}
                            />
                            <DetailRow
                                label="Nomor telepon"
                                value={loan.borrower.phone ?? '-'}
                            />
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Rincian Buku</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {items.length === 0 ? (
                            <p className="px-6 pb-6 text-sm text-gray-500">
                                Tidak ada rincian buku pada transaksi ini.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Judul</TableHead>
                                        <TableHead>Pengarang</TableHead>
                                        <TableHead className="text-right">
                                            Dipinjam
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Dikembalikan
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Sisa
                                        </TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {items.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="font-medium text-gray-900">
                                                {item.title ?? '-'}
                                            </TableCell>
                                            <TableCell>
                                                {item.author ?? '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {item.quantity}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {item.returned_quantity}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {item.remaining_quantity}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        item.status ===
                                                        'selesai'
                                                            ? 'secondary'
                                                            : 'success'
                                                    }
                                                >
                                                    {item.status === 'selesai'
                                                        ? 'Selesai'
                                                        : 'Dipinjam'}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Riwayat Pengembalian</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {returns.length === 0 ? (
                            <p className="px-6 pb-6 text-sm text-gray-500">
                                Belum ada pengembalian untuk transaksi ini.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Judul</TableHead>
                                        <TableHead>Tanggal Kembali</TableHead>
                                        <TableHead className="text-right">
                                            Jumlah
                                        </TableHead>
                                        <TableHead>Petugas</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {returns.map((entry) => (
                                        <TableRow key={entry.id}>
                                            <TableCell className="font-medium text-gray-900">
                                                {entry.title ?? '-'}
                                            </TableCell>
                                            <TableCell>
                                                {entry.return_date ?? '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {entry.quantity}
                                            </TableCell>
                                            <TableCell>
                                                {entry.petugas ?? '-'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <Link
                        href={route('admin.returns.index')}
                        className="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        <RotateCcw className="h-4 w-4" aria-hidden="true" />
                        Catat Pengembalian
                    </Link>

                    <Button
                        variant="outline"
                        disabled
                        title="Tersedia pada tahap pengembalian"
                    >
                        Cetak Bukti (belum tersedia)
                    </Button>
                </div>
            </div>
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
