import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import PublicLayout from '@/Layouts/PublicLayout';

export default function KatalogIndex() {
    return (
        <PublicLayout title="Katalog">
            <Card>
                <CardHeader>
                    <CardTitle>Katalog Buku</CardTitle>
                    <CardDescription>
                        Daftar koleksi buku perpustakaan.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-gray-600">
                        Katalog, pencarian, dan filter akan diimplementasikan
                        pada tahap berikutnya.
                    </p>
                </CardContent>
            </Card>
        </PublicLayout>
    );
}