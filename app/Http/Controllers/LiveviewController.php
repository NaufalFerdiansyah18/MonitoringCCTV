<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Unit;
use App\Services\StreamManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LiveviewController extends Controller
{
    public function __construct(private StreamManager $streams)
    {
        //
    }

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'camera_id' => ['required', 'integer'],
            'mode' => ['required', 'in:local,public'],
        ]);

        $camera = Camera::with('dvr.unit')->find($validated['camera_id']);
        $this->authorizeUnit($camera?->dvr?->unit);

        $result = $this->streams->start($camera, $validated['mode']);

        return response()->json($result);
    }

    public function stop(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stream_key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9\-_]+$/'],
        ]);

        $this->authorizeStreamKey($validated['stream_key']);

        $this->streams->stop($validated['stream_key']);

        return response()->json(['ok' => true]);
    }

    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stream_key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9\-_]+$/'],
        ]);

        $this->authorizeStreamKey($validated['stream_key']);

        $status = $this->streams->status($validated['stream_key']);

        return response()->json([
            'status' => $status,
            'error' => $status === 'failed' ? $this->streams->error($validated['stream_key']) : null,
        ]);
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

    private function authorizeStreamKey(string $streamKey): void
    {
        if (preg_match('/^cam-(\d+)$/', $streamKey, $m) !== 1) {
            return;
        }

        $camera = Camera::with('dvr.unit')->find((int) $m[1]);
        if ($camera !== null) {
            $this->authorizeUnit($camera->dvr->unit);
        }
    }
}
