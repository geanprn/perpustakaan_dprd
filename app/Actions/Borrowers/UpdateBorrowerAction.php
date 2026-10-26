<?php

namespace App\Actions\Borrowers;

use App\Models\Borrower;
use Illuminate\Support\Facades\DB;

class UpdateBorrowerAction
{
    /**
     * Perbarui data peminjam.
     *
     * Baris dikunci (lockForUpdate) agar dua petugas yang menyunting
     * bersamaan tidak saling menimpa.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Borrower $borrower, array $data): Borrower
    {
        return DB::transaction(function () use ($borrower, $data): Borrower {
            /** @var Borrower $locked */
            $locked = Borrower::query()->lockForUpdate()->findOrFail($borrower->getKey());

            $locked->fill($data)->save();

            return $locked;
        });
    }
}
