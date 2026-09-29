<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Recording;
use App\Models\Unit;
use App\Services\RecordingExporter;
use App\Services\RecordingManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class RecordingController extends Controller
{
    public function __construct(
        private RecordingManager $recordings,
        private RecordingExporter $exporter,
    ) {}

    public function index(Request $request): View
    {
        $categories = Auth::user()->allowedUnitCategories()->all();

        $recordings = Recording::query()
            ->with(['camera.dvr.unit'])
            ->whereHas('camera.dvr.unit', function ($query) use ($categories) {
                $query->whereIn('kategori', $categories);
            })
            ->when($request->filled('kategori'), function ($query) use ($request) {
                $query->whereHas('camera.dvr.unit', fn ($q) => $q->where('kategori', $request->string('kategori')));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status'));
            })
            ->latest('started_at')
            ->get();

        return view('recordings.index', [
            'recordings' => $recordings,
            'categories' => $categories,
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'camera_id' => ['required', 'integer'],
            'mode' => ['required', 'in:local,public'],
        ]);

        $camera = Camera::with('dvr.unit')->find($validated['camera_id']);
        $this->authorizeUnit($camera?->dvr?->unit);

        return response()->json($this->recordings->start($camera, $validated['mode']));
    }

    public function stop(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recording_id' => ['required', 'integer'],
        ]);

        $recording = Recording::find($validated['recording_id']);
        $this->authorizeRecording($recording);

        $this->recordings->stop($recording->id);

        return response()->json(['ok' => true]);
    }

    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recording_id' => ['required', 'integer'],
        ]);

        $recording = Recording::find($validated['recording_id']);
        $this->authorizeRecording($recording);

        $status = $this->recordings->status($recording->id);

        return response()->json([
            'status' => $status,
            'error' => $status === 'failed' ? $this->recordings->error($recording->id) : null,
        ]);
    }

    public function playlist(Recording $recording): Response
    {
        $this->authorizeRecording($recording);

        $streamKey = $recording->stream_key;
        if ($streamKey === null) {
            abort(404);
        }

        $playlist = public_path('hls').DIRECTORY_SEPARATOR.$streamKey.DIRECTORY_SEPARATOR.'index.m3u8';
        if (! is_file($playlist)) {
            abort(404);
        }

        $base = url('/hls/'.rawurlencode($streamKey));

        $lines = array_map(
            function (string $line) use ($base): string {
                $line = rtrim($line);

                return preg_match('/^segment_\d+\.ts$/', $line) === 1
                    ? $base.'/'.$line
                    : $line;
            },
            file($playlist) ?: [],
        );

        return response(implode("\n", $lines)."\n")
            ->header('Content-Type', 'application/vnd.apple.mpegurl');
    }

    public function export(Recording $recording)
    {
        $this->authorizeRecording($recording);

        try {
            return $this->exporter->export($recording);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['export' => $e->getMessage()]);
        }
    }

    private function authorizeRecording(?Recording $recording): void
    {
        if ($recording === null) {
            abort(404);
        }

        $this->authorizeUnit($recording->camera->load('dvr.unit')->dvr?->unit);
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
