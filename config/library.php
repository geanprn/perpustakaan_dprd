<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tampilkan Stok Persis
    |--------------------------------------------------------------------------
    |
    | Jika true, pengunjung melihat angka stok tersedia secara persis.
    | Jika false, hanya menampilkan status "Tersedia" atau "Habis".
    |
    */
    'show_exact_stock' => env('LIBRARY_SHOW_EXACT_STOCK', true),

    /*
    |--------------------------------------------------------------------------
    | Jumlah Item per Halaman
    |--------------------------------------------------------------------------
    |
    | Digunakan untuk paginasi daftar (katalog, buku, peminjam, dll).
    |
    */
    'per_page' => 10,
];
