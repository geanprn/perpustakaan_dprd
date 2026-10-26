<?php

namespace App\Models;

use Database\Factories\BorrowerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'identity_number', 'institution', 'phone', 'address'])]
class Borrower extends Model
{
    /** @use HasFactory<BorrowerFactory> */
    use HasFactory;

    /**
     * Peminjam memiliki banyak transaksi peminjaman.
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
