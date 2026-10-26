<?php

use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Public\CatalogController;
use App\Http\Controllers\Public\HomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Rute Publik
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/katalog', [CatalogController::class, 'index'])->name('catalog.index');

Route::get('/katalog/{book}', [CatalogController::class, 'show'])->name('catalog.show');

/*
|--------------------------------------------------------------------------
| Rute Admin — dilindungi middleware auth
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Pengaturan profil (hanya ubah password)
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Kategori buku (FR-BK-07)
    Route::get('/kategori', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/kategori', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/kategori/{kategori}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/kategori/{kategori}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Buku (FR-BK-01 sampai FR-BK-08)
    Route::get('/buku', [BookController::class, 'index'])->name('books.index');
    Route::post('/buku', [BookController::class, 'store'])->name('books.store');
    Route::put('/buku/{buku}', [BookController::class, 'update'])->name('books.update');
    Route::delete('/buku/{buku}', [BookController::class, 'destroy'])->name('books.destroy');

    // Menu placeholder — akan diisi di tahap berikutnya
    Route::get('/peminjam', fn () => Inertia::render('Admin/Peminjam/Index'))->name('borrowers.index');
    Route::get('/peminjaman', fn () => Inertia::render('Admin/Peminjaman/Index'))->name('loans.index');
    Route::get('/pengembalian', fn () => Inertia::render('Admin/Pengembalian/Index'))->name('returns.index');
    Route::get('/laporan', fn () => Inertia::render('Admin/Laporan/Index'))->name('reports.index');
});

require __DIR__.'/auth.php';
