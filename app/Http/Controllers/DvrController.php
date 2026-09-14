<?php

namespace App\Http\Controllers;

use App\Models\Dvr;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DvrController extends Controller
{
    public function index(Request $request): View
    {
        $query = Dvr::with(['unit', 'cameras']);

        if ($request->filled('unit')) {
            $query->where('unit_id', $request->integer('unit'));
        }

        return view('dvrs.index', [
            'dvrs' => $query->orderBy('nama')->paginate(10)->withQueryString(),
            'units' => Unit::orderBy('kode')->get(),
            'selectedUnit' => $request->integer('unit'),
        ]);
    }

    public function create(): View
    {
        return view('dvrs.create', ['units' => Unit::orderBy('kode')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);

        Dvr::create($data);

        return redirect()->route('admin.dvrs.index')
            ->with('success', 'DVR berhasil ditambahkan.');
    }

    public function edit(Dvr $dvr): View
    {
        return view('dvrs.edit', ['dvr' => $dvr, 'units' => Unit::orderBy('kode')->get()]);
    }

    public function update(Request $request, Dvr $dvr): RedirectResponse
    {
        $data = $this->validated($request);

        $dvr->fill([
            'unit_id' => $data['unit_id'],
            'nama' => $data['nama'],
            'ip_local' => $data['ip_local'],
            'port_local' => $data['port_local'],
            'ip_public' => $data['ip_public'] ?: null,
            'port_public' => $data['ip_public'] ? $data['port_public'] : null,
            'username' => $data['username'],
        ]);

        if (! empty($data['password'])) {
            $dvr->password = $data['password'];
        }

        $dvr->save();

        return redirect()->route('admin.dvrs.index')
            ->with('success', 'DVR berhasil diperbarui.');
    }

    public function destroy(Dvr $dvr): RedirectResponse
    {
        $dvr->delete();

        return redirect()->route('admin.dvrs.index')
            ->with('success', 'DVR berhasil dihapus.');
    }

    private function validated(Request $request, bool $passwordRequired = false): array
    {
        return $request->validate([
            'unit_id' => ['required', 'exists:units,id'],
            'nama' => ['required', 'string', 'max:255'],
            'ip_local' => ['required', 'ip'],
            'port_local' => ['required', 'integer', 'min:1', 'max:65535'],
            'ip_public' => ['nullable', 'ip'],
            'port_public' => ['nullable', 'integer', 'min:1', 'max:65535', 'required_with:ip_public'],
            'username' => ['required', 'string', 'max:255'],
            'password' => $passwordRequired ? ['required', 'string'] : ['nullable', 'string'],
        ]);
    }
}
