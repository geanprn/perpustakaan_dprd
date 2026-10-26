<?php

namespace App\Actions\Borrowers;

use App\Models\Borrower;
use Illuminate\Support\Facades\DB;

class CreateBorrowerAction
{
    /**
     * Buat data peminjam baru.
     *
     * Dipisah ke Action agar dapat dipakai ulang oleh form peminjaman
     * saat petugas mencatat peminjam baru (FR-PJ-05).
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Borrower
    {
        return DB::transaction(fn (): Borrower => Borrower::query()->create($data));
    }
}
