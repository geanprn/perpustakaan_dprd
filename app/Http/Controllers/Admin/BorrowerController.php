<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Borrowers\CreateBorrowerAction;
use App\Actions\Borrowers\DeleteBorrowerAction;
use App\Actions\Borrowers\UpdateBorrowerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBorrowerRequest;
use App\Http\Requests\Admin\UpdateBorrowerRequest;
use App\Models\Borrower;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BorrowerController extends Controller
{
    /**
     * Tampilkan daftar peminjam dengan pencarian dan paginasi (FR-PJ-01).
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $borrowers = Borrower::query()
            ->withCount('loans')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $pattern = "%{$search}%";

                $query->where(function (Builder $subQuery) use ($pattern): void {
                    $subQuery
                        ->where('name', 'like', $pattern)
                        ->orWhere('identity_number', 'like', $pattern);
                });
            })
            ->orderBy('name')
            ->paginate(config('library.per_page'))
            ->withQueryString();

        return Inertia::render('Admin/Peminjam/Index', [
            'borrowers' => $borrowers,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Tampilkan detail peminjam beserta riwayat transaksinya.
     *
     * Riwayat akan terisi setelah pencatatan peminjaman tersedia (Tahap 6).
     */
    public function show(Borrower $peminjam): Response
    {
        $peminjam->load([
            'loans' => fn ($query) => $query
                ->select(['id', 'loan_number', 'borrower_id', 'user_id', 'loan_date', 'due_date', 'status'])
                ->with(['user:id,name', 'loanItems:id,loan_id,quantity,returned_quantity,status'])
                ->latest('loan_date')
                ->latest('id'),
        ]);

        return Inertia::render('Admin/Peminjam/Show', [
            'borrower' => [
                'id' => (int) $peminjam->getKey(),
                'name' => $peminjam->name,
                'identity_number' => $peminjam->identity_number,
                'institution' => $peminjam->institution,
                'phone' => $peminjam->phone,
                'address' => $peminjam->address,
                'created_at' => $peminjam->created_at?->toIso8601String(),
            ],
            'loans' => $peminjam->loans->map(fn ($loan): array => [
                'id' => (int) $loan->getKey(),
                'loan_number' => $loan->loan_number,
                'loan_date' => $loan->loan_date?->toDateString(),
                'due_date' => $loan->due_date?->toDateString(),
                'status' => $loan->status->value,
                'petugas' => $loan->user?->name,
                'jumlah_judul' => $loan->loanItems->count(),
                'jumlah_eksemplar' => (int) $loan->loanItems->sum('quantity'),
            ])->values(),
        ]);
    }

    /**
     * Simpan peminjam baru (FR-PJ-02, FR-PJ-03).
     */
    public function store(StoreBorrowerRequest $request, CreateBorrowerAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return back()->with('success', 'Peminjam berhasil ditambahkan.');
    }

    /**
     * Perbarui data peminjam (FR-PJ-02, FR-PJ-03).
     */
    public function update(
        UpdateBorrowerRequest $request,
        Borrower $peminjam,
        UpdateBorrowerAction $action,
    ): RedirectResponse {
        $action->execute($peminjam, $request->validated());

        return back()->with('success', 'Data peminjam berhasil diperbarui.');
    }

    /**
     * Hapus data peminjam.
     *
     * Penghapusan ditolak bila peminjam punya riwayat transaksi
     * (BR-08, FR-PJ-04).
     */
    public function destroy(Borrower $peminjam, DeleteBorrowerAction $action): RedirectResponse
    {
        $terhapus = $action->execute($peminjam);

        if (! $terhapus) {
            return back()->with(
                'error',
                'Peminjam tidak dapat dihapus karena memiliki riwayat peminjaman.',
            );
        }

        return back()->with('success', 'Peminjam berhasil dihapus.');
    }
}
