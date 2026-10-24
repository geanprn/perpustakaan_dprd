<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Rute Publik
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Public/Home');
})->name('home');

Route::get('/katalog', function () {
    return Inertia::render('Public/Katalog/Index');
})->name('catalog.index');

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

    // Menu placeholder — akan diisi di tahap berikutnya
    Route::get('/buku', fn () => Inertia::render('Admin/Buku/Index'))->name('books.index');
    Route::get('/kategori', fn () => Inertia::render('Admin/Kategori/Index'))->name('categories.index');
    Route::get('/peminjam', fn () => Inertia::render('Admin/Peminjam/Index'))->name('borrowers.index');
    Route::get('/peminjaman', fn () => Inertia::render('Admin/Peminjaman/Index'))->name('loans.index');
    Route::get('/pengembalian', fn () => Inertia::render('Admin/Pengembalian/Index'))->name('returns.index');
    Route::get('/laporan', fn () => Inertia::render('Admin/Laporan/Index'))->name('reports.index');
});

require __DIR__.'/auth.php';
