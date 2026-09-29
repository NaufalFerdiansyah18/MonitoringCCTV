@extends('layouts.app')

@section('title', 'Rekaman — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <div>
            <h1>Rekaman</h1>
            <p style="color:#6b7280; font-size:13px; margin-top:4px;">
                Arsip rekaman per kamera. Kamera direkam terus-menerus selama tersedia.
            </p>
        </div>
    </div>

    <form class="card" method="GET" action="{{ route('recordings.index') }}">
        <div class="form-row">
            <div class="form-group">
                <label for="kategori">Kategori</label>
                <select id="kategori" name="kategori">
                    <option value="">— Semua —</option>
                    @foreach ($categories as $kategori)
                        <option value="{{ $kategori }}" @selected(request('kategori') === $kategori)>
                            {{ ucfirst($kategori) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">— Semua —</option>
                    @foreach (['recording', 'stopped', 'failed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="display:flex; align-items:flex-end; margin-bottom:0;">
                <button class="btn btn-secondary" type="submit">Filter</button>
            </div>
        </div>
    </form>

    <div class="card" id="rec-player-card" style="display:none;">
        <div class="page-head" style="margin-bottom:0;">
            <div>
                <h2 style="font-size:16px;" id="rec-player-title">Putar Rekaman</h2>
            </div>
            <button class="btn btn-secondary btn-xs" type="button" id="rec-player-close">Tutup</button>
        </div>
        <video id="rec-player" controls playsinline style="width:100%; max-width:960px; background:#000; border-radius:8px;"></video>
    </div>

    @if ($recordings->isEmpty())
        <div class="card empty">Belum ada rekaman.</div>
    @else
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Kamera</th>
                        <th>Unit</th>
                        <th>Kategori</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th>Durasi</th>
                        <th>Ukuran</th>
                        <th>Status</th>
                        <th style="width:220px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recordings as $recording)
                        <tr>
                            <td>CH {{ $recording->camera->channel }} — {{ $recording->camera->nama_lokasi }}</td>
                            <td>{{ $recording->camera->dvr->unit?->kode }} — {{ $recording->camera->dvr->unit?->nama }}</td>
                            <td>
                                <span class="badge badge-kategori">{{ ucfirst($recording->camera->dvr->unit?->kategori) }}</span>
                            </td>
                            <td>{{ $recording->started_at?->format('d M Y H:i:s') ?? '—' }}</td>
                            <td>{{ $recording->ended_at?->format('d M Y H:i:s') ?? '—' }}</td>
                            <td>
                                @if ($recording->duration_seconds !== null)
                                    {{ floor($recording->duration_seconds / 60) }}m {{ $recording->duration_seconds % 60 }}s
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($recording->size_bytes !== null)
                                    {{ number_format($recording->size_bytes / 1024 / 1024, 2) }} MB
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $recording->status === 'stopped' ? 'stopped' : $recording->status }}">
                                    {{ ucfirst($recording->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="actions" style="margin-top:0;">
                                    @if ($recording->status === 'recording')
                                        <form method="POST" action="{{ route('recordings.stop') }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="recording_id" value="{{ $recording->id }}">
                                            <button class="btn btn-danger btn-xs" type="submit">Hentikan</button>
                                        </form>
                                    @endif
                                    @if ($recording->stream_key)
                                        <button class="btn btn-primary btn-xs" type="button"
                                                data-play-title="CH {{ $recording->camera->channel }} — {{ $recording->camera->nama_lokasi }}"
                                                data-play-url="{{ route('recordings.playlist', $recording) }}">Putar</button>
                                        <a class="btn btn-secondary btn-xs" href="{{ route('recordings.export', $recording) }}">Export</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.17"></script>
    <script>
        (function () {
            const playerCard = document.getElementById('rec-player-card');
            const playerTitle = document.getElementById('rec-player-title');
            const player = document.getElementById('rec-player');
            let hls = null;

            function stopPlayback() {
                if (hls) {
                    hls.destroy();
                    hls = null;
                }
                player.removeAttribute('src');
                playerCard.style.display = 'none';
            }

            document.getElementById('rec-player-close').addEventListener('click', stopPlayback);

            document.querySelectorAll('[data-play-url]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const title = btn.dataset.playTitle;
                    const url = btn.dataset.playUrl;
                    playerTitle.textContent = 'Putar: ' + title;
                    playerCard.style.display = 'block';

                    if (hls) {
                        hls.destroy();
                    }

                    if (window.Hls && Hls.isSupported()) {
                        hls = new Hls({ liveDurationInfinity: true });
                        hls.loadSource(url);
                        hls.attachMedia(player);
                    } else if (player.canPlayType('application/vnd.apple.mpegurl')) {
                        player.src = url;
                    }
                });
            });
        })();
    </script>
@endpush