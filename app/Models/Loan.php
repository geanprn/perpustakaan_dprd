<?php

namespace App\Models;

use App\Enums\LoanStatus;
use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    #[Fillable(['loan_number', 'borrower_id', 'user_id', 'loan_date', 'due_date', 'status', 'notes'])]

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'loan_date' => 'date',
            'due_date' => 'date',
            'status' => LoanStatus::class,
        ];
    }

    /**
     * Transaksi dimiliki satu peminjam.
     */
    public function borrower(): BelongsTo
    {
        return $this->belongsTo(Borrower::class);
    }

    /**
     * Petugas yang mencatat transaksi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Transaksi memuat banyak rincian buku.
     */
    public function loanItems(): HasMany
    {
        return $this->hasMany(LoanItem::class);
    }

    /**
     * Apakah transaksi terlambat: due_date terisi, sudah lewat, dan belum selesai.
     */
    public function isOverdue(): bool
    {
        if ($this->status === LoanStatus::selesai) {
            return false;
        }

        if ($this->due_date === null) {
            return false;
        }

        return $this->due_date->lessThan(now()->startOfDay());
    }

    /**
     * Scope transaksi terlambat.
     *
     * @param  Builder<Loan>  $query
     * @return Builder<Loan>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->startOfDay())
            ->where('status', '!=', LoanStatus::selesai->value);
    }
}
