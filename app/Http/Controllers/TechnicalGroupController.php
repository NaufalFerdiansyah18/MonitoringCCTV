<?php

namespace App\Http\Controllers;

use App\Models\TechnicalGroup;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TechnicalGroupController extends Controller
{
    public function index(): View
    {
        $groups = TechnicalGroup::withCount('users')->with('unitCategoryRows')->orderBy('nama')->paginate(10);

        return view('technical-groups.index', ['groups' => $groups]);
    }

    public function create(): View
    {
        return view('technical-groups.create', ['kategoris' => Unit::KATEGORI]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateGroup($request);

        $group = TechnicalGroup::create(['nama' => $data['nama']]);
        $this->syncCategories($group, $data['kategoris'] ?? []);

        return redirect()->route('admin.technical-groups.index')
            ->with('success', 'Grup Teknis berhasil ditambahkan.');
    }

    public function edit(TechnicalGroup $technicalGroup): View
    {
        return view('technical-groups.edit', [
            'group' => $technicalGroup,
            'kategoris' => Unit::KATEGORI,
        ]);
    }

    public function update(Request $request, TechnicalGroup $technicalGroup): RedirectResponse
    {
        $data = $this->validateGroup($request, $technicalGroup);

        $technicalGroup->update(['nama' => $data['nama']]);
        $this->syncCategories($technicalGroup, $data['kategoris'] ?? []);

        return redirect()->route('admin.technical-groups.index')
            ->with('success', 'Grup Teknis berhasil diperbarui.');
    }

    public function destroy(TechnicalGroup $technicalGroup): RedirectResponse
    {
        $technicalGroup->delete();

        return redirect()->route('admin.technical-groups.index')
            ->with('success', 'Grup Teknis berhasil dihapus.');
    }

    private function validateGroup(Request $request, ?TechnicalGroup $group = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('technical_groups', 'nama')->ignore($group?->id)],
            'kategoris' => ['nullable', 'array'],
            'kategoris.*' => ['distinct', Rule::in(Unit::KATEGORI)],
        ]);
    }

    private function syncCategories(TechnicalGroup $group, array $kategoris): void
    {
        $group->unitCategoryRows()->delete();

        foreach ($kategoris as $kategori) {
            $group->unitCategoryRows()->create(['kategori' => $kategori]);
        }
    }
}
