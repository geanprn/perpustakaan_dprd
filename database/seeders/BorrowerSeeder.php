<?php

namespace Database\Seeders;

use App\Models\Borrower;
use Illuminate\Database\Seeder;

class BorrowerSeeder extends Seeder
{
    /**
     * Seed sekitar 10 peminjam contoh.
     */
    public function run(): void
    {
        $borrowers = [
            ['Ahmad Fauzi', '6371010101010001', 'Sekretariat DPRD', '081234567001', 'Jl. Melayu No. 12, Banjarmasin'],
            ['Siti Aminah', '6371010101010002', 'Bagian Umum', '081234567002', 'Jl. A. Yani Km 4, Banjarmasin'],
            ['Budi Santoso', '6371010101010003', 'Komisi A', '081234567003', 'Jl. Pangeran Samudera, Banjarmasin'],
            ['Dewi Lestari', '6371010101010004', 'Komisi B', '081234567004', 'Jl. Lambung Mangkurat, Banjarmasin'],
            ['Eko Prasetyo', '6371010101010005', 'Komisi C', '081234567005', 'Jl. Hasan Basri, Banjarmasin'],
            ['Fitriani', '6371010101010006', 'Bagian Keuangan', '081234567006', 'Jl. Sultan Adam, Banjarmasin'],
            ['Gunawan', '6371010101010007', 'Bagian Hukum', null, null],
            ['Hendra Wijaya', '6371010101010008', 'Sekretariat DPRD', '081234567008', 'Jl. Belitung Darat, Banjarmasin'],
            ['Indah Permata', '6371010101010009', 'Bagian Umum', null, null],
            ['Joko Susilo', '6371010101010010', 'Komisi D', '081234567010', 'Jl. Cemara Raya, Banjarmasin'],
        ];

        foreach ($borrowers as [$name, $identity, $institution, $phone, $address]) {
            Borrower::query()->firstOrCreate(
                ['identity_number' => $identity],
                [
                    'name' => $name,
                    'institution' => $institution,
                    'phone' => $phone,
                    'address' => $address,
                ],
            );
        }
    }
}
