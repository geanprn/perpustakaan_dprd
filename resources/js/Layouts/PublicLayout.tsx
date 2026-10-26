import { cn } from '@/lib/utils';
import { Head, Link, usePage } from '@inertiajs/react';
import { BookOpen, Menu, X } from 'lucide-react';
import { PropsWithChildren, useState } from 'react';

import type { PageProps } from '@/types';

const PUBLIC_NAV = [
    { label: 'Beranda', href: '/', matches: (url: string) => url === '/' },
    {
        label: 'Katalog',
        href: '/katalog',
        matches: (url: string) => url.startsWith('/katalog'),
    },
];

/**
 * Layout publik sederhana — header dengan nama perpustakaan,
 * tautan Beranda, Katalog, dan tombol Login Petugas.
 */
export default function PublicLayout({
    title,
    children,
}: PropsWithChildren<{ title?: string }>) {
    const [menuOpen, setMenuOpen] = useState(false);

    return (
        <div className="flex min-h-screen flex-col bg-gray-50">
            <Head title={title ?? 'Beranda'} />

            <header className="border-b border-gray-200 bg-white">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                    <Link href="/" className="flex items-center gap-2">
                        <BookOpen className="h-6 w-6 text-indigo-600" />
                        <span className="text-sm font-semibold text-gray-900 sm:text-base">
                            Perpustakaan DPRD Kota Banjarmasin
                        </span>
                    </Link>

                    <nav className="hidden items-center gap-6 md:flex">
                        <PublicLinks />
                    </nav>

                    <div className="flex items-center gap-2">
                        <Link
                            href="/login"
                            className="hidden text-sm font-medium text-gray-600 transition-colors hover:text-indigo-600 md:inline"
                        >
                            Login Petugas
                        </Link>

                        <button
                            type="button"
                            onClick={() => setMenuOpen((v) => !v)}
                            className="rounded-md p-2 text-gray-600 hover:bg-gray-100 md:hidden"
                            aria-label="Buka menu"
                        >
                            {menuOpen ? (
                                <X className="h-5 w-5" />
                            ) : (
                                <Menu className="h-5 w-5" />
                            )}
                        </button>
                    </div>
                </div>

                {menuOpen && (
                    <nav className="flex flex-col gap-1 border-t border-gray-200 px-4 py-3 md:hidden">
                        <PublicLinks onNavigate={() => setMenuOpen(false)} />
                        <Link
                            href="/login"
                            onClick={() => setMenuOpen(false)}
                            className="mt-2 rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Login Petugas
                        </Link>
                    </nav>
                )}
            </header>

            <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6">
                {children}
            </main>

            <footer className="border-t border-gray-200 bg-white">
                <div className="mx-auto max-w-6xl px-4 py-6 text-center text-sm text-gray-500 sm:px-6">
                    &copy; {new Date().getFullYear()} Perpustakaan DPRD Kota
                    Banjarmasin
                </div>
            </footer>
        </div>
    );
}

function PublicLinks({ onNavigate }: { onNavigate?: () => void }) {
    const { url } = usePage<PageProps>();

    return (
        <>
            {PUBLIC_NAV.map((item) => {
                const isActive = item.matches(url);

                return (
                    <Link
                        key={item.href}
                        href={item.href}
                        onClick={onNavigate}
                        className={cn(
                            'text-sm font-medium transition-colors',
                            isActive
                                ? 'text-indigo-600'
                                : 'text-gray-700 hover:text-indigo-600',
                        )}
                    >
                        {item.label}
                    </Link>
                );
            })}
        </>
    );
}