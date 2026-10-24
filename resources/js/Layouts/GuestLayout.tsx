import ApplicationLogo from '@/Components/ApplicationLogo';
import { Card, CardContent } from '@/Components/ui/card';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function GuestLayout({ children }: PropsWithChildren) {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-4 py-10">
            <Link href="/" className="flex flex-col items-center gap-2">
                <ApplicationLogo className="h-16 w-16 fill-current text-indigo-600" />
                <span className="text-center text-sm font-semibold text-gray-700">
                    Perpustakaan DPRD Kota Banjarmasin
                </span>
            </Link>

            <Card className="mt-6 w-full sm:max-w-md">
                <CardContent className="p-6">{children}</CardContent>
            </Card>
        </div>
    );
}