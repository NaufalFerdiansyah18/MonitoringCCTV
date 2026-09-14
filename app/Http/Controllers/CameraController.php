<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Dvr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CameraController extends Controller
{
    public function index(Dvr $dvr): View
    {
        return view('cameras.index', [
            'dvr' => $dvr->load('unit'),
            'cameras' => $dvr->cameras()->orderBy('channel')->get(),
        ]);
    }

    public function create(Dvr $dvr): View
    {
        return view('cameras.create', ['dvr' => $dvr->load('unit')]);
    }

    public function store(Request $request, Dvr $dvr): RedirectResponse
    {
        $data = $this->validated($request, $dvr);

        $dvr->cameras()->create($data);

        return redirect()->route('admin.dvrs.cameras.index', $dvr)
            ->with('success', 'Kamera berhasil ditambahkan.');
    }

    public function edit(Dvr $dvr, Camera $camera): View
    {
        abort_unless($camera->dvr_id === $dvr->id, 404);

        return view('cameras.edit', [
            'dvr' => $dvr->load('unit'),
            'camera' => $camera,
        ]);
    }

    public function update(Request $request, Dvr $dvr, Camera $camera): RedirectResponse
    {
        abort_unless($camera->dvr_id === $dvr->id, 404);

        $data = $this->validated($request, $dvr, $camera);

        $camera->update($data);

        return redirect()->route('admin.dvrs.cameras.index', $dvr)
            ->with('success', 'Kamera berhasil diperbarui.');
    }

    public function destroy(Dvr $dvr, Camera $camera): RedirectResponse
    {
        abort_unless($camera->dvr_id === $dvr->id, 404);

        $camera->delete();

        return redirect()->route('admin.dvrs.cameras.index', $dvr)
            ->with('success', 'Kamera berhasil dihapus.');
    }

    private function validated(Request $request, Dvr $dvr, ?Camera $camera = null): array
    {
        return $request->validate([
            'channel' => [
                'required',
                'integer',
                'between:1,16',
                Rule::unique('cameras', 'channel')->where('dvr_id', $dvr->id)->ignore($camera?->id),
            ],
            'nama_lokasi' => ['required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
