import Badge from '@/Components/ui/badge';
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
import { ArrowLeft } from 'lucide-react';

import type { BorrowerDetail, BorrowerLoan } from '@/types';

interface PeminjamShowProps {
    borrower: BorrowerDetail;
    loans: BorrowerLoan[];
}

/**
 * Detail peminjam — data diri dan riwayat transaksi (FR-PJ-04).
 *
 * Riwayat kosong bila peminjaman belum pernah dicatat (terisi setelah
 * pencatatan peminjaman tersedia).
 */
export default function PeminjamShow({ borrower, loans }: PeminjamShowProps) {
    return (
        <AdminLayout title="Detail Peminjam">
            <div className="space-y-6">
                <Link
                    href={route('admin.borrowers.index')}
                    className="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:underline"
                >
                    <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                    Kembali ke daftar peminjam
                </Link>

                <Card>
                    <CardHeader>
                        <CardTitle>Data Diri</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                            <DetailRow label="Nama" value={borrower.name} />
                            <DetailRow
                                label="Nomor identitas"
                                value={borrower.identity_number}
                            />
                            <DetailRow
                                label="Instansi/Unit"
                                value={borrower.institution ?? '-'}
                            />
                            <DetailRow
                                label="Nomor telepon"
                                value={borrower.phone ?? '-'}
                            />
                            <DetailRow
                                label="Alamat"
                                value={borrower.address ?? '-'}
                            />
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Riwayat Peminjaman</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {loans.length === 0 ? (
                            <p className="px-6 pb-6 text-sm text-gray-500">
                                Peminjam ini belum memiliki riwayat transaksi.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nomor Peminjaman</TableHead>
                                        <TableHead>Tanggal Pinjam</TableHead>
                                        <TableHead>Jatuh Tempo</TableHead>
                                        <TableHead className="text-right">
                                            Judul
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Eksemplar
                                        </TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Petugas</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {loans.map((loan) => (
                                        <TableRow key={loan.id}>
                                            <TableCell className="font-medium text-gray-900">
                                                {loan.loan_number}
                                            </TableCell>
                                            <TableCell>
                                                {loan.loan_date ?? '-'}
                                            </TableCell>
                                            <TableCell>
                                                {loan.due_date ?? '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {loan.jumlah_judul}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {loan.jumlah_eksemplar}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        loan.status ===
                                                        'selesai'
                                                            ? 'secondary'
                                                            : 'success'
                                                    }
                                                >
                                                    {loan.status === 'selesai'
                                                        ? 'Selesai'
                                                        : 'Berjalan'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {loan.petugas ?? '-'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
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
