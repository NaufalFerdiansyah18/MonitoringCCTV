<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        $units = Unit::query()
            ->whereIn('kategori', $user->allowedUnitCategories()->all())
            ->with('dvrs.cameras')
            ->orderBy('kategori')
            ->orderBy('nama')
            ->get();

        $selectedUnit = null;

        if ($request->filled('unit')) {
            $requestedUnit = Unit::find($request->integer('unit'));

            abort_unless($requestedUnit !== null, 404);
            abort_unless($units->contains('id', $requestedUnit->id), 403);

            $selectedUnit = $requestedUnit->load('dvrs.cameras');
        }

        return view('dashboard.index', [
            'units' => $units,
            'selectedUnit' => $selectedUnit,
            'mode' => config('cctv.mode'),
        ]);
    }
}
