<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Borrowers\CreateBorrowerAction;
use App\Actions\Loans\CreateLoanAction;
use App\Enums\LoanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBorrowerRequest;
use App\Http\Requests\Admin\StoreLoanRequest;
use App\Models\Book;
use App\Models\Borrower;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    /**
     * Daftar transaksi peminjaman dengan filter status dan pencarian
     * (FR-LN-09).
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $status = in_array($status, ['berjalan', 'selesai', 'terlambat'], true)
            ? $status
            : '';

        $loans = Loan::query()
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
                            $borrowerQuery
                                ->where('name', 'like', $pattern)
                                ->orWhere('identity_number', 'like', $pattern);
                        });
                });
            })
            ->when($status === 'berjalan', fn (Builder $query) => $query->where('status', LoanStatus::berjalan->value))
            ->when($status === 'selesai', fn (Builder $query) => $query->where('status', LoanStatus::selesai->value))
            ->when($status === 'terlambat', fn (Builder $query) => $query->overdue())
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
                'status' => $loan->status->value,
                'is_overdue' => $loan->isOverdue(),
                'jumlah_judul' => $loan->loanItems->count(),
                'jumlah_eksemplar' => (int) $loan->loanItems->sum('quantity'),
            ]);

        return Inertia::render('Admin/Peminjaman/Index', [
            'loans' => $loans,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Form pencatatan peminjaman baru (FR-LN-01, FR-LN-02, FR-LN-04).
     */
    public function create(Request $request): Response
    {
        $selectedBorrowerId = $request->query('borrower_id');

        return Inertia::render('Admin/Peminjaman/Create', [
            'borrowers' => Borrower::query()
                ->orderBy('name')
                ->get(['id', 'name', 'identity_number'])
                ->map(fn (Borrower $borrower): array => [
                    'id' => (int) $borrower->getKey(),
                    'name' => $borrower->name,
                    'identity_number' => $borrower->identity_number,
                ])
                ->values(),
            'books' => Book::query()
                ->where('available_copies', '>', 0)
                ->orderBy('title')
                ->get(['id', 'title', 'author', 'available_copies'])
                ->map(fn (Book $book): array => [
                    'id' => (int) $book->getKey(),
                    'title' => $book->title,
                    'author' => $book->author,
                    'available_copies' => (int) $book->available_copies,
                ])
                ->values(),
            'defaultLoanDate' => now()->toDateString(),
            'selectedBorrowerId' => $selectedBorrowerId !== null
                ? (int) $selectedBorrowerId
                : null,
        ]);
    }

    /**
     * Simpan transaksi peminjaman (FR-LN-06 sampai FR-LN-08).
     */
    public function store(StoreLoanRequest $request, CreateLoanAction $action): RedirectResponse
    {
        $loan = $action->execute([
            ...$request->validated(),
            'user_id' => (int) $request->user()->getKey(),
        ]);

        return redirect()
            ->route('admin.loans.show', $loan)
            ->with(
                'success',
                'Peminjaman berhasil dicatat dengan nomor transaksi '.$loan->loan_number.'.',
            );
    }

    /**
     * Detail transaksi beserta rincian buku dan riwayat pengembalian
     * (FR-LN-08).
     */
    public function show(Loan $peminjaman): Response
    {
        $peminjaman->load([
            'borrower:id,name,identity_number,institution,phone',
            'user:id,name',
            'loanItems' => fn ($query) => $query
                ->select(['id', 'loan_id', 'book_id', 'quantity', 'returned_quantity', 'status'])
                ->with([
                    'book:id,title,author',
                    'returns' => fn ($returnQuery) => $returnQuery
                        ->select(['id', 'loan_item_id', 'user_id', 'return_date', 'quantity'])
                        ->with('user:id,name')
                        ->orderBy('return_date')
                        ->orderBy('id'),
                ])
                ->orderBy('id'),
        ]);

        return Inertia::render('Admin/Peminjaman/Show', [
            'loan' => [
                'id' => (int) $peminjaman->getKey(),
                'loan_number' => $peminjaman->loan_number,
                'loan_date' => $peminjaman->loan_date?->toDateString(),
                'due_date' => $peminjaman->due_date?->toDateString(),
                'status' => $peminjaman->status->value,
                'is_overdue' => $peminjaman->isOverdue(),
                'notes' => $peminjaman->notes,
                'petugas' => $peminjaman->user?->name,
                'borrower' => [
                    'id' => (int) $peminjaman->borrower_id,
                    'name' => $peminjaman->borrower?->name,
                    'identity_number' => $peminjaman->borrower?->identity_number,
                    'institution' => $peminjaman->borrower?->institution,
                    'phone' => $peminjaman->borrower?->phone,
                ],
            ],
            'items' => $peminjaman->loanItems->map(fn ($item): array => [
                'id' => (int) $item->getKey(),
                'title' => $item->book?->title,
                'author' => $item->book?->author,
                'quantity' => (int) $item->quantity,
                'returned_quantity' => (int) $item->returned_quantity,
                'remaining_quantity' => max((int) $item->quantity - (int) $item->returned_quantity, 0),
                'status' => $item->status->value,
                'returns' => $item->returns->map(fn ($return): array => [
                    'id' => (int) $return->getKey(),
                    'return_date' => $return->return_date?->toDateString(),
                    'quantity' => (int) $return->quantity,
                    'petugas' => $return->user?->name,
                ])->values(),
            ])->values(),
        ]);
    }

    /**
     * Catat peminjam baru langsung dari form peminjaman tanpa meninggalkan
     * form (FR-PJ-05, FR-LN-01).
     *
     * Memakai ulang CreateBorrowerAction, lalu kembali ke form peminjaman
     * dengan peminjam baru sudah terpilih.
     */
    public function storeBorrower(StoreBorrowerRequest $request, CreateBorrowerAction $action): RedirectResponse
    {
        $borrower = $action->execute($request->validated());

        return redirect()
            ->route('admin.loans.create', ['borrower_id' => $borrower->getKey()])
            ->with('success', 'Peminjam berhasil ditambahkan.');
    }
}
