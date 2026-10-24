import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import AdminLayout from '@/Layouts/AdminLayout';

/**
 * Halaman placeholder — isi sebenarnya dikerjakan pada tahap berikutnya.
 */
export default function PlaceholderPage({
    title,
    description,
}: {
    title: string;
    description: string;
}) {
    return (
        <AdminLayout title={title}>
            <Card>
                <CardHeader>
                    <CardTitle>{title}</CardTitle>
                    <CardDescription>{description}</CardDescription>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-gray-600">
                        Halaman ini masih berupa kerangka. Fitur akan
                        diimplementasikan pada tahap berikutnya.
                    </p>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}