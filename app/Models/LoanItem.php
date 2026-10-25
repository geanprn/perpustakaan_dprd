<?php

namespace App\Models;

use App\Enums\LoanItemStatus;
use Database\Factories\LoanItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanItem extends Model
{
    /** @use HasFactory<LoanItemFactory> */
    use HasFactory;

    #[Fillable(['loan_id', 'book_id', 'quantity', 'returned_quantity', 'status'])]

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'returned_quantity' => 'integer',
            'status' => LoanItemStatus::class,
        ];
    }

    /**
     * Rincian dimiliki satu transaksi.
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Rincian merujuk satu buku.
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Rincian memiliki banyak catatan pengembalian (mendukung sebagian).
     */
    public function returns(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }
}
