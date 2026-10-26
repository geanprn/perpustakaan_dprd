<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Books\CreateBookAction;
use App\Actions\Books\DeleteBookAction;
use App\Actions\Books\UpdateBookAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBookRequest;
use App\Http\Requests\Admin\UpdateBookRequest;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    /**
     * Tampilkan daftar buku dengan pencarian dan paginasi (FR-BK-01).
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $books = Book::query()
            ->with('category')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%");
                });
            })
            ->orderBy('title')
            ->paginate(config('library.per_page'))
            ->withQueryString();

        return Inertia::render('Admin/Buku/Index', [
            'books' => $books,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Simpan buku baru. Stok tersedia disamakan dengan jumlah eksemplar (FR-BK-02).
     */
    public function store(StoreBookRequest $request, CreateBookAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return back()->with('success', 'Buku berhasil ditambahkan.');
    }

    /**
     * Perbarui data buku termasuk penyesuaian stok (FR-BK-03).
     */
    public function update(
        UpdateBookRequest $request,
        Book $buku,
        UpdateBookAction $action,
    ): RedirectResponse {
        $action->execute($buku, $request->validated());

        return back()->with('success', 'Buku berhasil diperbarui.');
    }

    /**
     * Hapus buku.
     *
     * Penghapusan ditolak bila buku sudah punya riwayat peminjaman
     * (BR-08, FR-BK-04).
     */
    public function destroy(Book $buku, DeleteBookAction $action): RedirectResponse
    {
        $terhapus = $action->execute($buku);

        if (! $terhapus) {
            return back()->with(
                'error',
                'Buku tidak dapat dihapus karena memiliki riwayat peminjaman.',
            );
        }

        return back()->with('success', 'Buku berhasil dihapus.');
    }
}
