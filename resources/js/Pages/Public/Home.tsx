import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import PublicLayout from '@/Layouts/PublicLayout';

export default function Home() {
    return (
        <PublicLayout title="Beranda">
            <Card>
                <CardHeader>
                    <CardTitle>Perpustakaan DPRD Kota Banjarmasin</CardTitle>
                    <CardDescription>
                        Selamat datang di katalog perpustakaan DPRD Kota
                        Banjarmasin.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-gray-600">
                        Pengantar, kotak pencarian, dan akses ke katalog akan
                        tersedia pada tahap berikutnya.
                    </p>
                </CardContent>
            </Card>
        </PublicLayout>
    );
}