<?php

namespace App\Http\Controllers;

use App\Models\Alarm;
use App\Models\Camera;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AlarmController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Auth::user()->allowedUnitCategories()->all();

        $alarms = Alarm::query()
            ->with(['camera.dvr.unit'])
            ->whereHas('camera.dvr.unit', function ($query) use ($categories) {
                $query->whereIn('kategori', $categories);
            })
            ->when($request->filled('kategori'), function ($query) use ($request) {
                $query->whereHas('camera.dvr.unit', fn ($q) => $q->where('kategori', $request->string('kategori')));
            })
            ->when($request->boolean('seen'), function ($query) {
                $query->whereNull('seen_at');
            })
            ->latest('started_at')
            ->get();

        return view('alarms.index', [
            'alarms' => $alarms,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'camera_id' => ['required', 'integer'],
            'message' => ['nullable', 'string', 'max:255'],
        ]);

        $camera = Camera::with('dvr.unit')->find($validated['camera_id']);
        $this->authorizeUnit($camera?->dvr?->unit);

        Alarm::create([
            'camera_id' => $camera->id,
            'type' => 'manual',
            'message' => $validated['message'] ?: null,
            'started_at' => now(),
        ]);

        return back()->with('success', 'Alarm manual berhasil dibuat.');
    }

    public function seen(Alarm $alarm): RedirectResponse
    {
        $alarm->update(['seen_at' => now()]);

        return back()->with('success', 'Alarm ditandai sudah dilihat.');
    }

    private function authorizeUnit(?Unit $unit): void
    {
        if ($unit === null) {
            abort(404);
        }

        $categories = Auth::user()->allowedUnitCategories()->all();
        if (! in_array($unit->kategori, $categories, true)) {
            abort(403);
        }
    }
}
