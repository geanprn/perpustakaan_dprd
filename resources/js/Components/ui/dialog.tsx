import { cn } from '@/lib/utils';
import { Dialog as HeadlessDialog, DialogPanel, DialogTitle, Transition, TransitionChild } from '@headlessui/react';
import { X } from 'lucide-react';
import { Fragment, PropsWithChildren, ReactNode } from 'react';

interface DialogProps extends PropsWithChildren {
    show: boolean;
    onClose: () => void;
    title: string;
    description?: ReactNode;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
    closeable?: boolean;
}

const maxWidthClasses: Record<NonNullable<DialogProps['maxWidth']>, string> = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
};

/**
 * Dialog umum berbasis Headless UI — dipakai untuk form tambah/ubah data.
 */
export default function Dialog({
    show,
    onClose,
    title,
    description,
    maxWidth = 'lg',
    closeable = true,
    children,
}: DialogProps) {
    const handleClose = () => {
        if (closeable) {
            onClose();
        }
    };

    return (
        <Transition show={show} as={Fragment} leave="duration-200">
            <HeadlessDialog
                as="div"
                className="relative z-50"
                onClose={handleClose}
            >
                <TransitionChild
                    as={Fragment}
                    enter="ease-out duration-300"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in duration-200"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div
                        className="fixed inset-0 bg-gray-500/75"
                        aria-hidden="true"
                    />
                </TransitionChild>

                <div className="fixed inset-0 overflow-y-auto">
                    <div className="flex min-h-full items-end justify-center p-4 sm:items-center">
                        <TransitionChild
                            as={Fragment}
                            enter="ease-out duration-300"
                            enterFrom="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            enterTo="opacity-100 translate-y-0 sm:scale-100"
                            leave="ease-in duration-200"
                            leaveFrom="opacity-100 translate-y-0 sm:scale-100"
                            leaveTo="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        >
                            <DialogPanel
                                className={cn(
                                    'w-full transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all',
                                    maxWidthClasses[maxWidth],
                                )}
                            >
                                <div className="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-4">
                                    <div className="space-y-1">
                                        <DialogTitle className="text-base font-semibold text-gray-900">
                                            {title}
                                        </DialogTitle>
                                        {description && (
                                            <p className="text-sm text-gray-500">
                                                {description}
                                            </p>
                                        )}
                                    </div>

                                    <button
                                        type="button"
                                        onClick={handleClose}
                                        className="rounded-md p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                                        aria-label="Tutup dialog"
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
                                </div>

                                <div className="px-6 py-4">{children}</div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </HeadlessDialog>
        </Transition>
    );
}
