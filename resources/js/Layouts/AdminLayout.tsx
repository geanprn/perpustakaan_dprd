import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    BookCopy,
    BookOpen,
    FileBarChart,
    LayoutDashboard,
    Library,
    LogOut,
    Menu,
    RotateCcw,
    Tags,
    UserCog,
    Users,
    X,
} from 'lucide-react';
import { PropsWithChildren, ReactNode, useState } from 'react';

import type { PageProps } from '@/types';

interface NavItem {
    label: string;
    href: string;
    icon: ReactNode;
    active: (url: string) => boolean;
}

const NAV_ITEMS: NavItem[] = [
    {
        label: 'Dashboard',
        href: '/admin/dashboard',
        icon: <LayoutDashboard className="h-5 w-5" />,
        active: (url) => url.startsWith('/admin/dashboard'),
    },
    {
        label: 'Buku',
        href: '/admin/buku',
        icon: <BookOpen className="h-5 w-5" />,
        active: (url) => url.startsWith('/admin/buku'),
    },
    {
        label: 'Kategori',
        href: '/admin/kategori',
        icon: <Tags className="h-5 w-5" />,
        active: (url) => url.startsWith('/admin/kategori'),
    },
    {
        label: 'Peminjam',
        href: '/admin/peminjam',
        icon: <Users className="h-5 w-5" />,
        active: (url) => url.startsWith('/admin/peminjam'),
    },
    {
        label: 'Peminjaman',
        href: '/admin/peminjaman',
        icon: <Library className="h-5 w-5" />,
        active: (url) => url.startsWith('/admin/peminjaman'),
    },
    {
        label: 'Pengembalian',
        href: '/admin/pengembalian',
        icon: <RotateCcw className="h-5 w-5" />,
        active: (url) => url.startsWith('/admin/pengembalian'),
    },
    {
        label: 'Laporan',
        href: '/admin/laporan',
        icon: <FileBarChart className="h-5 w-5" />,
        active: (url) => url.startsWith('/admin/laporan'),
    },
];

/**
 * Layout admin — sidebar responsif dengan menu utama.
 */
export default function AdminLayout({
    title,
    children,
}: PropsWithChildren<{ title: string }>) {
    const [sidebarOpen, setSidebarOpen] = useState(false);

    return (
        <div className="min-h-screen bg-gray-100">
            <Head title={title} />
            <AdminHeader onToggleSidebar={() => setSidebarOpen((v) => !v)} />

            <div className="flex">
                <AdminSidebar
                    open={sidebarOpen}
                    onClose={() => setSidebarOpen(false)}
                />

                <main className="min-w-0 flex-1 p-4 sm:p-6">
                    <h1 className="mb-6 text-xl font-semibold text-gray-900 sm:text-2xl">
                        {title}
                    </h1>
                    {children}
                </main>
            </div>
        </div>
    );
}

function AdminHeader({ onToggleSidebar }: { onToggleSidebar: () => void }) {
    const { auth } = usePage<PageProps>().props;

    const logout = () => {
        router.post('/logout');
    };

    return (
        <header className="sticky top-0 z-30 border-b border-gray-200 bg-white">
            <div className="flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <div className="flex items-center gap-3">
                    <button
                        type="button"
                        onClick={onToggleSidebar}
                        className="rounded-md p-2 text-gray-600 hover:bg-gray-100 md:hidden"
                        aria-label="Buka menu"
                    >
                        <Menu className="h-5 w-5" />
                    </button>

                    <Link href="/admin/dashboard" className="flex items-center gap-2">
                        <BookCopy className="h-6 w-6 text-indigo-600" />
                        <span className="text-sm font-semibold text-gray-900 sm:text-base">
                            Perpustakaan DPRD Kota Banjarmasin
                        </span>
                    </Link>
                </div>

                <div className="flex items-center gap-3">
                    <div className="hidden text-right sm:block">
                        <p className="text-sm font-medium text-gray-900">
                            {auth.user?.name}
                        </p>
                        <p className="text-xs text-gray-500">
                            @{auth.user?.username}
                        </p>
                    </div>

                    <Link
                        href="/admin/profil"
                        className="rounded-md p-2 text-gray-600 hover:bg-gray-100"
                        aria-label="Pengaturan profil"
                    >
                        <UserCog className="h-5 w-5" />
                    </Link>

                    <Button
                        variant="outline"
                        size="sm"
                        onClick={logout}
                        aria-label="Logout"
                    >
                        <LogOut className="h-4 w-4" />
                        <span className="hidden sm:inline">Logout</span>
                    </Button>
                </div>
            </div>
        </header>
    );
}

function AdminSidebar({
    open,
    onClose,
}: {
    open: boolean;
    onClose: () => void;
}) {
    const { url } = usePage();

    return (
        <>
            {open && (
                <div
                    className="fixed inset-0 z-30 bg-black/40 md:hidden"
                    onClick={onClose}
                    aria-hidden="true"
                />
            )}

            <aside
                className={cn(
                    'fixed inset-y-0 left-0 z-40 w-64 shrink-0 overflow-y-auto border-r border-gray-200 bg-white p-4 transition-transform md:static md:z-auto md:translate-x-0',
                    open ? 'translate-x-0' : '-translate-x-full',
                )}
            >
                <div className="mb-4 flex items-center justify-between md:hidden">
                    <span className="text-sm font-semibold text-gray-900">
                        Menu
                    </span>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-md p-2 text-gray-600 hover:bg-gray-100"
                        aria-label="Tutup menu"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                <nav className="space-y-1">
                    {NAV_ITEMS.map((item) => {
                        const isActive = item.active(url);

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                onClick={onClose}
                                className={cn(
                                    'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                    isActive
                                        ? 'bg-indigo-50 text-indigo-700'
                                        : 'text-gray-700 hover:bg-gray-100',
                                )}
                            >
                                {item.icon}
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>
            </aside>
        </>
    );
}