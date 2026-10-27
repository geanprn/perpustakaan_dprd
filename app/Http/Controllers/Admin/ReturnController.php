<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Returns\RecordReturnAction;
use App\Enums\LoanItemStatus;
use App\Enums\LoanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReturnRequest;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReturnController extends Controller
{
    /**
     * Daftar transaksi yang belum selesai, dengan pencarian nomor transaksi
     * atau nama peminjam (FR-RT-01).
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $loans = Loan::query()
            ->where('status', '!=', LoanStatus::selesai->value)
            ->with([
                'borrower:id,name,identity_number',
                'loanItems:id,loan_id,quantity,returned_quantity',
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $pattern = "%{$search}%";

                $query->where(function (Builder $subQuery) use ($pattern): void {
                    $subQuery
                        ->where('loan_number', 'like', $pattern)
                        ->orWhereHas('borrower', function (Builder $borrowerQuery) use ($pattern): void {
                            $borrowerQuery->where('name', 'like', $pattern);
                        });
                });
            })
            ->latest('loan_date')
            ->latest('id')
            ->paginate(config('library.per_page'))
            ->withQueryString()
            ->through(fn (Loan $loan): array => [
                'id' => (int) $loan->getKey(),
                'loan_number' => $loan->loan_number,
                'borrower' => [
                    'id' => (int) $loan->borrower_id,
                    'name' => $loan->borrower?->name,
                    'identity_number' => $loan->borrower?->identity_number,
                ],
                'loan_date' => $loan->loan_date?->toDateString(),
                'due_date' => $loan->due_date?->toDateString(),
                'is_overdue' => $loan->isOverdue(),
                'jumlah_judul' => $loan->loanItems->count(),
                'jumlah_eksemplar' => (int) $loan->loanItems->sum('quantity'),
                'sisa_eksemplar' => (int) $loan->loanItems->sum(
                    fn ($item): int => max((int) $item->quantity - (int) $item->returned_quantity, 0),
                ),
            ]);

        return Inertia::render('Admin/Pengembalian/Index', [
            'loans' => $loans,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Form pencatatan pengembalian untuk satu transaksi (FR-RT-02, FR-RT-03).
     */
    public function create(Loan $peminjaman): Response|RedirectResponse
    {
        $peminjaman->load([
            'borrower:id,name,identity_number,institution,phone',
            'user:id,name',
            'loanItems' => fn ($query) => $query
                ->select(['id', 'loan_id', 'book_id', 'quantity', 'returned_quantity', 'status'])
                ->with('book:id,title,author')
                ->orderBy('id'),
        ]);

        if ($peminjaman->status === LoanStatus::selesai) {
            return redirect()
                ->route('admin.loans.show', $peminjaman)
                ->with('error', RecordReturnAction::PESAN_SUDAH_SELESAI);
        }

        return Inertia::render('Admin/Pengembalian/Create', [
            'loan' => [
                'id' => (int) $peminjaman->getKey(),
                'loan_number' => $peminjaman->loan_number,
                'loan_date' => $peminjaman->loan_date?->toDateString(),
                'due_date' => $peminjaman->due_date?->toDateString(),
                'status' => $peminjaman->status->value,
                'is_overdue' => $peminjaman->isOverdue(),
                'petugas' => $peminjaman->user?->name,
                'borrower' => [
                    'id' => (int) $peminjaman->borrower_id,
                    'name' => $peminjaman->borrower?->name,
                    'identity_number' => $peminjaman->borrower?->identity_number,
                    'institution' => $peminjaman->borrower?->institution,
                    'phone' => $peminjaman->borrower?->phone,
                ],
            ],
            'items' => $peminjaman->loanItems
                ->filter(fn ($item): bool => $item->status !== LoanItemStatus::selesai)
                ->map(fn ($item): array => [
                    'id' => (int) $item->getKey(),
                    'title' => $item->book?->title,
                    'author' => $item->book?->author,
                    'quantity' => (int) $item->quantity,
                    'returned_quantity' => (int) $item->returned_quantity,
                    'remaining_quantity' => max((int) $item->quantity - (int) $item->returned_quantity, 0),
                ])
                ->values(),
            'defaultReturnDate' => now()->toDateString(),
        ]);
    }

    /**
     * Simpan pencatatan pengembalian (FR-RT-05 sampai FR-RT-08).
     */
    public function store(
        StoreReturnRequest $request,
        Loan $peminjaman,
        RecordReturnAction $action,
    ): RedirectResponse {
        $loan = $action->execute([
            'loan_id' => (int) $peminjaman->getKey(),
            'user_id' => (int) $request->user()->getKey(),
            'return_date' => $request->validated('return_date'),
            'items' => $request->validated('items'),
        ]);

        return redirect()
            ->route('admin.loans.show', $loan)
            ->with(
                'success',
                'Pengembalian berhasil dicatat untuk transaksi '.$loan->loan_number.'.',
            );
    }
}
