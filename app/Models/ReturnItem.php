<?php

namespace App\Models;

use Database\Factories\ReturnItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['loan_item_id', 'user_id', 'return_date', 'quantity'])]
class ReturnItem extends Model
{
    /** @use HasFactory<ReturnItemFactory> */
    use HasFactory;

    /**
     * Nama tabel mengikuti PRD (tabel "returns").
     */
    protected $table = 'returns';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'quantity' => 'integer',
        ];
    }

    /**
     * Catatan pengembalian merujuk satu rincian peminjaman.
     */
    public function loanItem(): BelongsTo
    {
        return $this->belongsTo(LoanItem::class);
    }

    /**
     * Petugas yang mencatat pengembalian.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
