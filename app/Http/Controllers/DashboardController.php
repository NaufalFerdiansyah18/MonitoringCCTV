<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $units = Unit::query()
            ->whereIn('kategori', $user->allowedUnitCategories()->all())
            ->orderBy('kategori')
            ->orderBy('nama')
            ->get();

        return view('dashboard.index', ['units' => $units]);
    }
}
