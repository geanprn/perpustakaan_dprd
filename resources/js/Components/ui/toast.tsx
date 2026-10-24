import { cn } from '@/lib/utils';
import { X } from 'lucide-react';
import { HTMLAttributes } from 'react';

export type ToastVariant = 'success' | 'error';

const variantClasses: Record<ToastVariant, string> = {
    success: 'border-green-200 bg-green-50 text-green-900',
    error: 'border-red-200 bg-red-50 text-red-900',
};

const iconClasses: Record<ToastVariant, string> = {
    success: 'bg-green-500',
    error: 'bg-red-500',
};

export interface ToastProps extends HTMLAttributes<HTMLDivElement> {
    variant?: ToastVariant;
    title: string;
    description?: string;
    onClose?: () => void;
}

export default function Toast({
    variant = 'success',
    title,
    description,
    onClose,
    className,
    ...props
}: ToastProps) {
    return (
        <div
            role="alert"
            aria-live="polite"
            className={cn(
                'pointer-events-auto flex w-full items-start gap-3 rounded-lg border p-4 shadow-lg',
                variantClasses[variant],
                className,
            )}
            {...props}
        >
            <span
                className={cn('mt-1 h-2 w-2 shrink-0 rounded-full', iconClasses[variant])}
                aria-hidden="true"
            />

            <div className="flex-1 space-y-1">
                <p className="text-sm font-semibold">{title}</p>
                {description && (
                    <p className="text-sm opacity-90">{description}</p>
                )}
            </div>

            <button
                type="button"
                onClick={onClose}
                className="rounded-md p-1 text-gray-500 transition-colors hover:bg-black/5 hover:text-gray-800"
                aria-label="Tutup notifikasi"
            >
                <X className="h-4 w-4" />
            </button>
        </div>
    );
}