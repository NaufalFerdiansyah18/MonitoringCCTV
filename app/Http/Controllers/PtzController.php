<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Unit;
use App\Services\PtzService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PtzController extends Controller
{
    public function __construct(private PtzService $ptz) {}

    public function send(Request $request, Camera $camera): JsonResponse
    {
        $this->authorizeUnit($camera->load('dvr.unit')->dvr?->unit);

        if (! $camera->can_ptz) {
            return response()->json(['ok' => false, 'error' => 'Kamera ini tidak mendukung PTZ.'], 422);
        }

        $validated = $request->validate([
            'action' => ['required', 'in:start,stop'],
            'code' => ['required', Rule::in(PtzService::CODES)],
        ]);

        if ($validated['action'] === 'stop') {
            $validated['code'] = 'Stop';
        }

        return response()->json($this->ptz->send($camera, $validated['action'], $validated['code']));
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
