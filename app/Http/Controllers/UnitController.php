<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(): View
    {
        $units = Unit::withCount('dvrs')->orderBy('kode')->paginate(10);

        return view('units.index', ['units' => $units]);
    }

    public function create(): View
    {
        return view('units.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Unit::create($data);

        return redirect()->route('admin.units.index')
            ->with('success', 'Unit berhasil ditambahkan.');
    }

    public function edit(Unit $unit): View
    {
        return view('units.edit', ['unit' => $unit]);
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $data = $this->validated($request, $unit);

        $unit->update($data);

        return redirect()->route('admin.units.index')
            ->with('success', 'Unit berhasil diperbarui.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $unit->delete();

        return redirect()->route('admin.units.index')
            ->with('success', 'Unit berhasil dihapus.');
    }

    private function validated(Request $request, ?Unit $unit = null): array
    {
        return $request->validate([
            'kode' => ['required', 'string', 'max:255', Rule::unique('units', 'kode')->ignore($unit?->id)],
            'nama' => ['required', 'string', 'max:255'],
            'kategori' => ['required', Rule::in(Unit::KATEGORI)],
        ]);
    }
}
