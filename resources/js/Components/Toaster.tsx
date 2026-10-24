import Toast, { ToastVariant } from '@/Components/ui/toast';
import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

import type { PageProps } from '@/types';
import type { FlashMessages } from '@/types';

interface ToastItem {
    id: number;
    variant: ToastVariant;
    message: string;
}

const AUTO_DISMISS_MS = 5000;

/**
 * Toaster global — membaca flash message 'success' dan 'error' dari Inertia
 * lalu menampilkannya sebagai toast yang menutup otomatis.
 */
export default function Toaster() {
    const { flash } = usePage<PageProps>().props;
    const [toasts, setToasts] = useState<ToastItem[]>([]);
    const counter = useRef(0);

    useEffect(() => {
        const incoming: ToastItem[] = [];

        (['success', 'error'] as ToastVariant[]).forEach((variant) => {
            const message = (flash as FlashMessages | undefined)?.[variant];

            if (message) {
                counter.current += 1;
                incoming.push({ id: counter.current, variant, message });
            }
        });

        if (incoming.length > 0) {
            setToasts((current) => [...current, ...incoming]);
        }
    }, [flash]);

    const dismiss = (id: number) => {
        setToasts((current) => current.filter((toast) => toast.id !== id));
    };

    useEffect(() => {
        if (toasts.length === 0) {
            return;
        }

        const timers = toasts.map((toast) =>
            window.setTimeout(() => dismiss(toast.id), AUTO_DISMISS_MS),
        );

        return () => timers.forEach((timer) => window.clearTimeout(timer));
    }, [toasts]);

    if (toasts.length === 0) {
        return null;
    }

    return (
        <div className="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4 sm:inset-x-auto sm:right-4 sm:items-end">
            {toasts.map((toast) => (
                <Toast
                    key={toast.id}
                    variant={toast.variant}
                    title={
                        toast.variant === 'success'
                            ? 'Berhasil'
                            : 'Terjadi Kesalahan'
                    }
                    description={toast.message}
                    onClose={() => dismiss(toast.id)}
                    className="sm:w-96"
                />
            ))}
        </div>
    );
}