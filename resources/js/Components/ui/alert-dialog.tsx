import { Button } from '@/Components/ui/button';
import {
    Dialog as HeadlessDialog,
    DialogPanel,
    DialogTitle,
    Transition,
    TransitionChild,
} from '@headlessui/react';
import { AlertTriangle } from 'lucide-react';
import { Fragment, ReactNode } from 'react';

interface AlertDialogProps {
    show: boolean;
    title: string;
    description: ReactNode;
    confirmLabel?: string;
    cancelLabel?: string;
    processing?: boolean;
    onConfirm: () => void;
    onClose: () => void;
}

/**
 * Dialog konfirmasi tindakan merusak (hapus data).
 *
 * Tidak dapat ditutup saat proses berjalan agar pengguna tidak
 * meninggalkan aksi di tengah jalan.
 */
export default function AlertDialog({
    show,
    title,
    description,
    confirmLabel = 'Hapus',
    cancelLabel = 'Batal',
    processing = false,
    onConfirm,
    onClose,
}: AlertDialogProps) {
    return (
        <Transition show={show} as={Fragment} leave="duration-200">
            <HeadlessDialog
                as="div"
                className="relative z-50"
                onClose={() => {
                    if (!processing) {
                        onClose();
                    }
                }}
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
                            <DialogPanel className="w-full transform overflow-hidden rounded-lg bg-white shadow-xl transition-all sm:max-w-md">
                                <div className="flex gap-4 px-6 py-5">
                                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                                        <AlertTriangle className="h-5 w-5" />
                                    </span>

                                    <div className="space-y-1">
                                        <DialogTitle className="text-base font-semibold text-gray-900">
                                            {title}
                                        </DialogTitle>
                                        <div className="text-sm text-gray-600">
                                            {description}
                                        </div>
                                    </div>
                                </div>

                                <div className="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                                    <Button
                                        variant="outline"
                                        onClick={onClose}
                                        disabled={processing}
                                    >
                                        {cancelLabel}
                                    </Button>

                                    <Button
                                        variant="destructive"
                                        onClick={onConfirm}
                                        disabled={processing}
                                    >
                                        {processing ? 'Memproses...' : confirmLabel}
                                    </Button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </HeadlessDialog>
        </Transition>
    );
}
