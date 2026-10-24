<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman dashboard admin.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'user' => auth()->user(),
        ]);
    }
}
