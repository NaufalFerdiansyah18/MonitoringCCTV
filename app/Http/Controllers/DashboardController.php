<?php

namespace App\Http\Controllers;

use App\Models\Dvr;
use App\Models\Recording;
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
        $selectedDvr = null;
        $kategori = trim((string) $request->string('kategori'));

        if ($request->filled('dvr')) {
            $selectedDvr = Dvr::with(['unit', 'cameras'])->find($request->integer('dvr'));

            abort_unless($selectedDvr !== null, 404);
            abort_unless($units->contains('id', $selectedDvr->unit_id), 403);

            $selectedUnit = $selectedDvr->unit;
            $selectedUnit->setRelation('dvrs', collect([$selectedDvr]));
        } elseif ($request->filled('unit')) {
            $requestedUnit = Unit::find($request->integer('unit'));

            abort_unless($requestedUnit !== null, 404);
            abort_unless($units->contains('id', $requestedUnit->id), 403);

            $selectedUnit = $requestedUnit->load('dvrs.cameras');
        }

        $cameraKategoris = $selectedUnit
            ? $selectedUnit->dvrs->flatMap(fn ($dvr) => $dvr->cameras->pluck('kategori'))
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all()
            : [];

        if ($selectedUnit !== null && $kategori !== '') {
            $selectedUnit->dvrs->each(function (Dvr $dvr) use ($kategori) {
                $dvr->setRelation('cameras', $dvr->cameras->where('kategori', $kategori)->values());
            });
        }

        $cameraIds = $selectedUnit
            ? $selectedUnit->dvrs->flatMap(fn ($dvr) => $dvr->cameras->pluck('id'))->all()
            : [];

        $recordingCameraIds = Recording::query()
            ->whereIn('camera_id', $cameraIds)
            ->where('status', 'recording')
            ->pluck('camera_id')
            ->all();

        return view('dashboard.index', [
            'units' => $units,
            'selectedUnit' => $selectedUnit,
            'selectedDvr' => $selectedDvr,
            'mode' => config('cctv.mode'),
            'recordingCameraIds' => $recordingCameraIds,
            'kategori' => $kategori,
            'cameraKategoris' => $cameraKategoris,
        ]);
    }
}
