import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import AdminLayout from '@/Layouts/AdminLayout';
import { usePage } from '@inertiajs/react';

import type { PageProps } from '@/types';

export default function Dashboard() {
    const { auth } = usePage<PageProps>().props;

    return (
        <AdminLayout title="Dashboard">
            <Card>
                <CardHeader>
                    <CardTitle>
                        Selamat datang, {auth.user?.name ?? 'Petugas'}!
                    </CardTitle>
                    <CardDescription>
                        Anda masuk sebagai petugas perpustakaan. Gunakan menu di
                        samping untuk mengelola koleksi dan transaksi.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-gray-600">
                        Ringkasan statistik akan tersedia pada tahap
                        berikutnya.
                    </p>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}