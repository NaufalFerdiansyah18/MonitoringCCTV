@extends('layouts.app')

@section('title', 'Liveview — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <h1>Liveview</h1>
    </div>

    @if (session('liveview_error'))
        <div class="alert alert-error">{{ session('liveview_error') }}</div>
    @endif

    <form class="card" method="GET" action="{{ route('dashboard') }}">
        <div class="form-group" style="margin-bottom:0;">
            <label for="unit">Pilih Unit</label>
            <div class="form-row">
                <select id="unit" name="unit" style="max-width:360px;">
                    <option value="">— Pilih Unit —</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected($selectedUnit?->id === $unit->id)>
                            {{ $unit->kode }} — {{ $unit->nama }} ({{ ucfirst($unit->kategori) }})
                        </option>
                    @endforeach
                </select>
                <button class="btn btn-secondary" type="submit">Tampilkan</button>
            </div>
        </div>
    </form>

    @if ($selectedUnit === null)
        @if ($units->isEmpty())
            <div class="card empty">Anda belum memiliki akses ke unit mana pun.</div>
        @else
            <p style="color:#6b7280; margin:18px 0 12px; font-size:14px;">
                Pilih unit untuk mulai menonton:
            </p>
            <div class="grid-units">
                @foreach ($units as $unit)
                    <a class="unit-card" style="text-decoration:none; color:inherit;"
                       href="{{ route('dashboard', ['unit' => $unit->id]) }}">
                        <h3>{{ $unit->nama }}</h3>
                        <p>{{ $unit->kode }}</p>
                        <div>
                            <span class="badge badge-kategori">{{ ucfirst($unit->kategori) }}</span>
                        </div>
                        <p style="margin-top:12px; color:#2563eb; font-size:12px;">
                            {{ $unit->dvrs->sum(fn ($dvr) => $dvr->cameras->count()) }} kamera →
                        </p>
                    </a>
                @endforeach
            </div>
        @endif
    @else
        <div class="card">
            <div class="page-head" style="margin-bottom:0;">
                <div>
                    <h2 style="font-size:18px;">{{ $selectedUnit->kode }} — {{ $selectedUnit->nama }}</h2>
                    <p style="color:#6b7280; font-size:13px; margin-top:4px;">
                        Kategori {{ ucfirst($selectedUnit->kategori) }} · {{ $selectedUnit->dvrs->sum(fn ($dvr) => $dvr->cameras->count()) }} kamera
                    </p>
                </div>
                <a class="btn btn-secondary" href="{{ route('dashboard') }}">Ganti Unit</a>
            </div>
        </div>

        <div class="card lv-controls">
            <div class="form-row">
                <div class="form-group">
                    <label for="lv-mode">Mode</label>
                    <select id="lv-mode">
                        <option value="local" @selected($mode !== 'public')>Local</option>
                        <option value="public" @selected($mode === 'public')>Public</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="lv-grid">Grid</label>
                    <select id="lv-grid">
                        <option value="1">1</option>
                        <option value="4" selected>4</option>
                        <option value="8">8</option>
                        <option value="16">16</option>
                    </select>
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end; gap:10px; margin-bottom:0;">
                    <button id="lv-start" class="btn btn-primary" type="button">Mulai Streaming</button>
                    <button id="lv-stop-all" class="btn btn-secondary" type="button">Hentikan Semua</button>
                </div>
            </div>
        </div>

        <div id="lv-alert" class="alert alert-error" style="display:none;"></div>

        @if ($selectedUnit->dvrs->isEmpty())
            <div class="card empty">Belum ada DVR pada unit ini.</div>
        @else
            @foreach ($selectedUnit->dvrs as $dvr)
                <div class="card lv-dvr">
                    <h2 style="font-size:15px; margin-bottom:12px;">DVR {{ $dvr->nama }}</h2>

                    @if ($dvr->cameras->isEmpty())
                        <p style="color:#9ca3af; font-size:13px;">Belum ada kamera pada DVR ini.</p>
                    @else
                        <div class="lv-cameras">
                            @foreach ($dvr->cameras as $camera)
                                <label class="lv-cam">
                                    <input type="checkbox"
                                           data-camera-id="{{ $camera->id }}"
                                           data-channel="{{ $camera->channel }}"
                                           data-name="{{ $camera->nama_lokasi }}"
                                           data-dvr="{{ $dvr->nama }}"
                                           data-public="{{ $dvr->ip_public ? '1' : '0' }}">
                                    <span>CH {{ $camera->channel }} — {{ $camera->nama_lokasi }}</span>
                                    @if ($camera->kategori)
                                        <span class="badge badge-kategori">{{ $camera->kategori }}</span>
                                    @endif
                                    @if (! $dvr->ip_public)
                                        <span class="lv-tag">tidak tersedia di Public</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        @endif

        <div id="lv-grid" class="lv-grid"></div>
    @endif

    <style>
        .lv-cameras { display: flex; flex-direction: column; gap: 8px; }
        .lv-cam {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 8px;
            font-size: 14px; cursor: pointer; transition: background .12s;
        }
        .lv-cam:hover { background: #f9fafb; }
        .lv-cam input[type="checkbox"] { width: 16px; height: 16px; }
        .lv-cam.lv-cam-disabled { opacity: .45; pointer-events: none; }
        .lv-tag { margin-left: auto; font-size: 11px; color: #9ca3af; }
        .lv-controls .form-group select { min-width: 160px; }

        .lv-grid {
            margin-top: 18px;
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(1, 1fr);
        }
        .lv-grid[data-cols="2"] { grid-template-columns: repeat(2, 1fr); }
        .lv-grid[data-cols="4"] { grid-template-columns: repeat(4, 1fr); }
        .lv-grid[data-cols="8"] { grid-template-columns: repeat(8, 1fr); }

        .lv-tile {
            background: #000;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            position: relative;
        }
        .lv-tile video { width: 100%; aspect-ratio: 16 / 9; display: block; background: #000; }
        .lv-cap {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            padding: 8px 10px; background: #111827; color: #fff; font-size: 12px;
        }
        .lv-cap .lv-cap-btn {
            background: #374151; color: #fff; border: none; border-radius: 6px;
            padding: 4px 10px; font-size: 11px; cursor: pointer;
        }
        .lv-cap .lv-cap-btn:hover { background: #4b5563; }
        .lv-status { font-weight: 600; }
        .lv-status-connected { color: #34d399; }
        .lv-status-starting { color: #fbbf24; }
        .lv-status-failed { color: #f87171; }

        @media (max-width: 900px) {
            .lv-grid[data-cols="4"], .lv-grid[data-cols="8"] { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.17"></script>
    <script>
        (function () {
            if (!document.getElementById('lv-grid')) {
                return;
            }

            const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const URLS = {
                start: @json(route('liveview.start')),
                stop: @json(route('liveview.stop')),
                status: @json(route('liveview.status')),
            };
            const DEFAULT_MODE = @json($mode);

            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
            };

            const state = {
                mode: DEFAULT_MODE,
                grid: 4,
                streams: {},
            };

            const gridEl = document.getElementById('lv-grid');
            const alertEl = document.getElementById('lv-alert');
            const modeEl = document.getElementById('lv-mode');
            const gridSelect = document.getElementById('lv-grid');

            function showAlert(message) {
                alertEl.textContent = message;
                alertEl.style.display = 'block';
            }

            function hideAlert() {
                alertEl.style.display = 'none';
            }

            function applyModeVisibility() {
                const isPublic = state.mode === 'public';
                document.querySelectorAll('.lv-cam').forEach(function (label) {
                    const box = label.querySelector('input[type="checkbox"]');
                    if (isPublic && box.dataset.public !== '1') {
                        box.checked = false;
                        label.classList.add('lv-cam-disabled');
                    } else {
                        label.classList.remove('lv-cam-disabled');
                    }
                });
            }

            function updateGridCols() {
                const count = Object.keys(state.streams).length;
                let cols = 1;
                if (count > 1 && count <= 4) cols = 2;
                if (count > 4) cols = 4;
                gridEl.setAttribute('data-cols', String(cols));
            }

            function setStatus(streamKey, status) {
                const stream = state.streams[streamKey];
                if (!stream) return;
                stream.statusEl.textContent = status === 'connected' ? 'LIVE'
                    : status === 'starting' ? 'menyiapkan' : 'gagal';
                stream.statusEl.className = 'lv-status lv-status-' + status;
            }

            function addTile(cameraData, streamKey) {
                const tile = document.createElement('div');
                tile.className = 'lv-tile';

                const video = document.createElement('video');
                video.autoplay = true;
                video.muted = true;
                video.playsInline = true;
                video.controls = true;

                const cap = document.createElement('div');
                cap.className = 'lv-cap';
                cap.innerHTML = '<span>CH ' + cameraData.channel + ' — ' + cameraData.name
                    + ' <small>(' + cameraData.dvr + ')</small></span>';

                const statusEl = document.createElement('span');
                statusEl.className = 'lv-status lv-status-starting';
                statusEl.textContent = 'menyiapkan';
                cap.appendChild(statusEl);

                const stopBtn = document.createElement('button');
                stopBtn.className = 'lv-cap-btn';
                stopBtn.type = 'button';
                stopBtn.textContent = 'Stop';
                cap.appendChild(stopBtn);

                tile.appendChild(video);
                tile.appendChild(cap);
                gridEl.appendChild(tile);

                const hls = new Hls({ liveSyncDurationCount: 3, maxLiveSyncPlaybackRate: 1.5 });
                hls.loadSource('/hls/' + encodeURIComponent(streamKey) + '/index.m3u8');
                hls.attachMedia(video);

                hls.on(Hls.Events.ERROR, function (_e, data) {
                    if (data.fatal) {
                        setStatus(streamKey, 'failed');
                    }
                });
                video.addEventListener('loadeddata', function () {
                    setStatus(streamKey, 'connected');
                });

                const stream = {
                    hls: hls,
                    tile: tile,
                    statusEl: statusEl,
                    poller: null,
                };
                state.streams[streamKey] = stream;
                updateGridCols();

                const poll = function () {
                    if (!state.streams[streamKey]) return;
                    fetch(URLS.status + '?stream_key=' + encodeURIComponent(streamKey), { headers: headers })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            setStatus(streamKey, data.status);
                            if (data.status === 'failed' && data.error) {
                                showAlert('Stream ' + streamKey + ' bermasalah: ' + data.error);
                            }
                        })
                        .catch(function () { /* abaikan error polling */ });
                };
                stream.poller = setInterval(poll, 3000);

                stopBtn.addEventListener('click', function () {
                    stopStream(streamKey);
                });
            }

            function stopStream(streamKey) {
                const stream = state.streams[streamKey];
                if (stream && stream.poller) {
                    clearInterval(stream.poller);
                }
                fetch(URLS.stop, {
                    method: 'POST',
                    headers: headers,
                    body: JSON.stringify({ stream_key: streamKey }),
                }).catch(function () {});
                if (stream) {
                    stream.hls.destroy();
                    stream.tile.remove();
                    delete state.streams[streamKey];
                    updateGridCols();
                }
            }

            function stopAllStreams() {
                Object.keys(state.streams).forEach(stopStream);
            }

            async function startStreams() {
                const mode = state.mode;
                const gridLimit = parseInt(gridSelect.value, 10) || 4;

                const selected = Array.prototype.slice.call(
                    document.querySelectorAll('.lv-cam input[type="checkbox"]:checked')
                ).slice(0, gridLimit);

                if (selected.length === 0) {
                    showAlert('Pilih minimal satu kamera.');
                    return;
                }

                hideAlert();

                for (const box of selected) {
                    const cameraData = {
                        channel: box.dataset.channel,
                        name: box.dataset.name,
                        dvr: box.dataset.dvr,
                    };

                    let response;
                    try {
                        response = await fetch(URLS.start, {
                            method: 'POST',
                            headers: headers,
                            body: JSON.stringify({ camera_id: parseInt(box.dataset.cameraId, 10), mode: mode }),
                        });
                    } catch (e) {
                        showAlert('Gagal terhubung ke server.');
                        break;
                    }

                    if (response.status === 403 || response.status === 404) {
                        showAlert('Akses ke kamera ini ditolak.');
                        break;
                    }

                    let data;
                    try {
                        data = await response.json();
                    } catch (e) {
                        showAlert('Respons server tidak valid.');
                        break;
                    }

                    if (!data.ok) {
                        showAlert(data.error || 'Stream gagal dimulai.');
                        break;
                    }

                    addTile(cameraData, data.streamKey);
                }
            }

            document.getElementById('lv-start').addEventListener('click', startStreams);
            document.getElementById('lv-stop-all').addEventListener('click', stopAllStreams);

            modeEl.addEventListener('change', function () {
                state.mode = modeEl.value;
                stopAllStreams();
                applyModeVisibility();
            });

            gridSelect.addEventListener('change', function () {
                state.grid = parseInt(gridSelect.value, 10) || 4;
                stopAllStreams();
            });

            window.addEventListener('pagehide', function () {
                Object.keys(state.streams).forEach(function (streamKey) {
                    fetch(URLS.stop, {
                        method: 'POST',
                        headers: headers,
                        keepalive: true,
                        body: JSON.stringify({ stream_key: streamKey }),
                    }).catch(function () {});
                });
                state.streams = {};
            });

            applyModeVisibility();
        })();
    </script>
@endpush