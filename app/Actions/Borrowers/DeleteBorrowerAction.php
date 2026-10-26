<?php

namespace App\Actions\Borrowers;

use App\Models\Borrower;
use Illuminate\Support\Facades\DB;

class DeleteBorrowerAction
{
    /**
     * Hapus data peminjam.
     *
     * Mengembalikan false bila peminjam sudah punya riwayat transaksi
     * (BR-08, FR-PJ-04) sehingga riwayat tetap utuh.
     */
    public function execute(Borrower $borrower): bool
    {
        return DB::transaction(function () use ($borrower): bool {
            /** @var Borrower $locked */
            $locked = Borrower::query()->lockForUpdate()->findOrFail($borrower->getKey());

            if ($locked->loans()->exists()) {
                return false;
            }

            $locked->delete();

            return true;
        });
    }
}
