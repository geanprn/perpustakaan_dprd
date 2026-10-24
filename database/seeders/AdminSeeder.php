<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Buat satu akun admin/petugas dari kredensial di .env.
     */
    public function run(): void
    {
        $username = env('ADMIN_USERNAME', 'admin');
        $password = env('ADMIN_PASSWORD', 'password');

        User::query()->updateOrCreate(
            ['username' => $username],
            [
                'name' => 'Administrator Perpustakaan',
                'password' => Hash::make($password),
            ],
        );
    }
}
