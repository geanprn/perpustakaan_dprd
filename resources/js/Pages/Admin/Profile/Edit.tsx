import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import AdminLayout from '@/Layouts/AdminLayout';
import { Transition } from '@headlessui/react';
import { useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

import type { PageProps } from '@/types';

export default function ProfileEdit() {
    const { auth } = usePage<PageProps>().props;
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const {
        data,
        setData,
        errors,
        put,
        reset,
        processing,
        recentlySuccessful,
    } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('admin.profile.password'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (errs) => {
                if (errs.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (errs.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <AdminLayout title="Pengaturan Profil">
            <div className="max-w-2xl space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Akun</CardTitle>
                        <CardDescription>
                            Akun petugas tidak dapat diubah dari halaman ini.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <div>
                            <span className="text-gray-500">Nama: </span>
                            <span className="font-medium text-gray-900">
                                {auth.user?.name}
                            </span>
                        </div>
                        <div>
                            <span className="text-gray-500">Username: </span>
                            <span className="font-medium text-gray-900">
                                {auth.user?.username}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Ubah Password</CardTitle>
                        <CardDescription>
                            Gunakan password yang panjang dan acak agar akun
                            tetap aman.
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form onSubmit={updatePassword} className="space-y-4">
                            <div>
                                <Label htmlFor="current_password">
                                    Password Saat Ini
                                </Label>
                                <Input
                                    id="current_password"
                                    ref={currentPasswordInput}
                                    type="password"
                                    className="mt-1"
                                    autoComplete="current-password"
                                    value={data.current_password}
                                    onChange={(e) =>
                                        setData(
                                            'current_password',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={errors.current_password}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <Label htmlFor="password">
                                    Password Baru
                                </Label>
                                <Input
                                    id="password"
                                    ref={passwordInput}
                                    type="password"
                                    className="mt-1"
                                    autoComplete="new-password"
                                    value={data.password}
                                    onChange={(e) =>
                                        setData('password', e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.password}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <Label htmlFor="password_confirmation">
                                    Konfirmasi Password Baru
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    className="mt-1"
                                    autoComplete="new-password"
                                    value={data.password_confirmation}
                                    onChange={(e) =>
                                        setData(
                                            'password_confirmation',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                    className="mt-2"
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>
                                    Simpan
                                </Button>

                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-gray-600">
                                        Tersimpan.
                                    </p>
                                </Transition>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}