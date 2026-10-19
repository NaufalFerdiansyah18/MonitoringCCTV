@extends('layouts.app')

@section('title', 'Liveview — CCTV Monitoring')

@section('content')
    <div class="page-head" style="margin-bottom: 16px;">
        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <h1 style="margin:0; font-size:22px; font-weight:700;">
                @if ($selectedDvr)
                    Liveview — DVR {{ $selectedDvr->nama }}
                @elseif ($selectedUnit)
                    Liveview CCTV — {{ $selectedUnit->nama }}
                @else
                    Liveview CCTV
                @endif
            </h1>
            @if ($selectedDvr)
                <span class="badge" style="background:#ede9fe; color:#6d28d9; font-size:12px; padding:4px 10px;">DVR Khusus</span>
                <span class="badge badge-kategori" style="font-size:12px; padding:4px 10px;">{{ ucfirst($selectedUnit->kategori) }}</span>
                <a href="{{ route('dashboard', ['unit' => $selectedUnit->id]) }}" class="btn btn-secondary btn-xs" style="margin-left:auto;">
                    ← Tampilkan Semua DVR di Unit
                </a>
            @elseif ($selectedUnit)
                <span class="badge badge-kategori" style="font-size:12px; padding:4px 10px;">{{ ucfirst($selectedUnit->kategori) }}</span>
                @if ($units->count() > 1)
                    <a href="{{ route('dashboard', ['select_unit' => 1]) }}" class="btn btn-secondary btn-xs" style="margin-left:auto; display:inline-flex; align-items:center; gap:4px;" title="Lihat semua unit dalam bentuk kartu">
                        ⊞ Pilih Unit Lain
                    </a>
                @endif
            @endif
        </div>
    </div>

    @if (session('liveview_error'))
        <div class="alert alert-error">{{ session('liveview_error') }}</div>
    @endif

    @if ($selectedUnit === null)
        <form class="card" method="GET" action="{{ route('dashboard') }}">
            <div class="form-group" style="margin-bottom:0;">
                <label for="unit">Pilih Unit</label>
                <div class="form-row">
                    <select id="unit" name="unit" style="max-width:360px;">
                        <option value="">— Pilih Unit —</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}">
                                {{ $unit->kode }} — {{ $unit->nama }} ({{ ucfirst($unit->kategori) }})
                            </option>
                        @endforeach
                    </select>
                    <button class="btn btn-secondary" type="submit">Tampilkan</button>
                </div>
            </div>
        </form>

        @if ($units->isEmpty())
            <div class="card empty">Anda belum memiliki akses ke unit mana pun.</div>
        @else
            <p style="color:#6b7280; margin:18px 0 12px; font-size:14px;">
                Pilih unit untuk mulai menonton siaran CCTV:
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
        {{-- Unified Control Toolbar --}}
        <div class="card lv-toolbar-card">
            <div class="lv-toolbar-row">
                <div class="lv-toolbar-left">
                    <form method="GET" action="{{ route('dashboard') }}" style="display:flex; align-items:center; gap:8px; margin:0;">
                        <label for="unit" style="font-weight:600; font-size:13px; margin:0; white-space:nowrap; color:#4b5563;">Unit:</label>
                        <select id="unit" name="unit" onchange="this.form.submit()" style="font-size:13px; padding:6px 10px; border-radius:6px; border:1px solid #d1d5db; background:#fff;">
                            @foreach ($units as $u)
                                <option value="{{ $u->id }}" @selected($selectedUnit->id === $u->id)>
                                    {{ $u->kode }} — {{ $u->nama }} ({{ ucfirst($u->kategori) }})
                                </option>
                            @endforeach
                        </select>
                        <noscript><button class="btn btn-secondary btn-xs" type="submit">Pilih</button></noscript>
                    </form>

                    <form method="GET" action="{{ route('dashboard') }}" style="display:flex; align-items:center; gap:8px; margin:0;">
                        <input type="hidden" name="unit" value="{{ $selectedUnit->id }}">
                        <label for="kategori" style="font-weight:600; font-size:13px; margin:0; white-space:nowrap; color:#4b5563;">Kategori:</label>
                        <select id="kategori" name="kategori" onchange="this.form.submit()" style="font-size:13px; padding:6px 10px; border-radius:6px; border:1px solid #d1d5db; background:#fff;">
                            <option value="">Semua Kategori</option>
                            @foreach ($cameraKategoris as $kat)
                                <option value="{{ $kat }}" @selected($kategori === $kat)>{{ ucfirst($kat) }}</option>
                            @endforeach
                        </select>
                        <noscript><button class="btn btn-secondary btn-xs" type="submit">Terapkan</button></noscript>
                    </form>
                </div>

                <div class="lv-toolbar-right">
                    <div class="lv-tool-group">
                        <label for="lv-mode" style="font-size:12px; color:#6b7280; font-weight:600;">Mode:</label>
                        <select id="lv-mode" style="font-size:13px; padding:6px 10px; border-radius:6px; border:1px solid #d1d5db; background:#fff;">
                            <option value="local" @selected($mode !== 'public')>Local</option>
                            <option value="public" @selected($mode === 'public')>Public</option>
                        </select>
                    </div>

                    <div class="lv-tool-group">
                        <label for="lv-grid-select" style="font-size:12px; color:#6b7280; font-weight:600;">Grid:</label>
                        <select id="lv-grid-select" style="font-size:13px; padding:6px 10px; border-radius:6px; border:1px solid #d1d5db; background:#fff;">
                            <option value="1">1</option>
                            <option value="4" selected>4</option>
                            <option value="8">8</option>
                            <option value="16">16</option>
                        </select>
                    </div>

                    <div class="lv-actions">
                        <span id="lv-active-count" class="badge" style="background:#e5e7eb; color:#374151; font-weight:600; padding:6px 10px; font-size:12px;">
                            0 Kamera Aktif
                        </span>
                        <button id="lv-start-all" class="btn btn-primary" type="button" style="display:flex; align-items:center; gap:6px;" title="Putar semua kamera di unit ini secara otomatis">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            Putar Semua
                        </button>
                        <button id="lv-stop-all" class="btn btn-secondary btn-danger-tint" type="button" style="display:flex; align-items:center; gap:6px;" title="Hentikan semua siaran kamera">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M6 6h12v12H6z"/>
                            </svg>
                            Hentikan Semua
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="lv-alert" class="alert alert-error" style="display:none;"></div>

        {{-- Workstation 2-Column: Sidebar (Kamera) & Monitor (Video Grid) --}}
        <div class="lv-workspace">
            {{-- Left Panel: Camera List & DVRs --}}
            <div class="lv-sidebar-panel">
                <div class="lv-sidebar-header">
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <div>
                            <h3 style="font-size:14px; font-weight:700; margin:0; color:#111827;">Daftar Kamera</h3>
                            <p style="font-size:11px; color:#6b7280; margin:2px 0 0;">Klik kamera untuk langsung mulai live streaming</p>
                        </div>
                        <span id="lv-channel-total" class="badge" style="background:#f3f4f6; color:#4b5563; font-weight:600;">
                            {{ $selectedUnit->dvrs->sum(fn ($dvr) => $dvr->cameras->count()) }} total
                        </span>
                    </div>
                </div>

                @if ($selectedUnit->dvrs->isEmpty())
                    <div class="card empty" style="padding:14px; font-size:13px;">Belum ada DVR pada unit ini.</div>
                @else
                    @foreach ($selectedUnit->dvrs as $dvr)
                        <div class="card lv-dvr-card">
                            <div class="lv-dvr-title">
                                <div>
                                    <strong style="font-size:13px; color:#111827;">DVR {{ $dvr->nama }}</strong>
                                    <div style="font-size:11px; color:#9ca3af; margin-top:1px;">
                                        {{ $dvr->ip_public ? 'Public: '.$dvr->ip_public : 'Local: '.$dvr->ip_local }}
                                    </div>
                                </div>
                                <span class="badge" style="font-size:11px; background:#f3f4f6; color:#4b5563;">
                                    {{ $dvr->cameras->count() }} ch
                                </span>
                            </div>

                            @if ($dvr->cameras->isEmpty())
                                <p style="color:#9ca3af; font-size:12px; margin:8px 0 0;">Belum ada kamera pada DVR ini.</p>
                            @else
                                <div class="lv-cameras">
                                    @foreach ($dvr->cameras as $camera)
                                        <div class="lv-cam-box">
                                            <div class="lv-cam-card"
                                                 data-camera-id="{{ $camera->id }}"
                                                 data-channel="{{ $camera->channel }}"
                                                 data-name="{{ $camera->nama_lokasi }}"
                                                 data-dvr="{{ $dvr->nama }}"
                                                 data-public="{{ $dvr->ip_public ? '1' : '0' }}"
                                                 tabindex="0"
                                                 role="button"
                                                 title="Klik untuk langsung memutar siaran kamera ini">
                                                <div class="lv-cam-card-left">
                                                    <div class="lv-cam-play-btn" title="Status Pemutaran">
                                                        <svg class="lv-icon-idle-play" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                                            <path d="M8 5v14l11-7z"/>
                                                        </svg>
                                                        <span class="lv-pulse-beacon" style="display:none;"></span>
                                                    </div>
                                                    <div class="lv-cam-info-wrap">
                                                        <span class="lv-cam-title">CH {{ $camera->channel }} — {{ $camera->nama_lokasi }}</span>
                                                        <div class="lv-cam-meta-tags">
                                                            <span class="lv-status-dot {{ $camera->is_online ? 'is-online' : 'is-offline' }}"
                                                                  title="{{ $camera->is_online ? 'Online' : 'Offline' }}"></span>
                                                            <span class="lv-cam-online-label">{{ $camera->is_online ? 'Online' : 'Offline' }}</span>
                                                            @if ($camera->kategori)
                                                                <span class="badge badge-kategori" style="font-size:10px; padding:1px 6px;">{{ $camera->kategori }}</span>
                                                            @endif
                                                            @if (in_array($camera->id, $recordingCameraIds))
                                                                <span class="badge badge-recording" style="font-size:10px; padding:1px 6px;">Rec</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="lv-cam-badge-state">
                                                    <span class="lv-stream-pill">Putar</span>
                                                </div>
                                            </div>

                                            <div class="lv-cam-subactions">
                                                @if ($camera->can_ptz)
                                                    <div class="lv-ptz">
                                                        @foreach (['Up', 'Left', 'Right', 'Down', 'ZoomIn', 'ZoomOut', 'Stop'] as $code)
                                                            <button class="ptz-send btn-xs" type="button"
                                                                    data-url="{{ route('ptz.send', $camera) }}"
                                                                    data-code="{{ $code }}">{{ $code === 'Stop' ? 'Stop' : $code }}</button>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                <div class="lv-quick-tools">
                                                    <button class="rec-restart btn-xs" type="button"
                                                            data-url="{{ route('recordings.start') }}"
                                                            data-camera-id="{{ $camera->id }}">
                                                        {{ in_array($camera->id, $recordingCameraIds) ? 'Rekam Ulang' : 'Rekam' }}
                                                    </button>
                                                    <form method="POST" action="{{ route('alarms.store') }}" style="display:inline;"
                                                          onsubmit="return confirm('Buat alarm manual untuk CH {{ $camera->channel }}?');">
                                                        @csrf
                                                        <input type="hidden" name="camera_id" value="{{ $camera->id }}">
                                                        <button class="btn-xs btn-alarm-trigger" type="submit">Alarm</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Right Panel: Surveillance Video Monitor --}}
            <div class="lv-monitor-panel">
                {{-- Placeholder when no stream is playing --}}
                <div id="lv-empty-placeholder" class="lv-empty-monitor">
                    <svg class="lv-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <h3 style="font-size:16px; font-weight:700; color:#374151; margin-bottom:6px;">Monitor Siaran Kamera CCTV</h3>
                    <p style="font-size:13px; color:#6b7280; max-width:440px; margin:0 auto 16px; line-height:1.5;">
                        Pilih kamera di panel sebelah kiri untuk langsung memutar siaran secara otomatis tanpa perlu checklist.
                    </p>
                    <button id="lv-quick-start" class="btn btn-primary" type="button" style="font-size:13px; display:inline-flex; align-items:center; gap:6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                        Putar Semua Kamera Langsung
                    </button>
                </div>

                {{-- Video Player Grid Container --}}
                <div id="lv-grid" class="lv-grid"></div>
            </div>
        </div>
    @endif

    <style>
        /* Toolbar */
        .lv-toolbar-card {
            padding: 12px 18px;
            margin-bottom: 16px;
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .lv-toolbar-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .lv-toolbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .lv-toolbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .lv-tool-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .lv-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-danger-tint {
            color: #b91c1c;
            border-color: #fecaca;
            background: #fff;
        }
        .btn-danger-tint:hover {
            background: #fee2e2;
            color: #991b1b;
        }

        /* 2-Column Workstation Layout */
        .lv-workspace {
            display: grid;
            grid-template-columns: 330px 1fr;
            gap: 18px;
            align-items: start;
        }
        @media (max-width: 1024px) {
            .lv-workspace {
                grid-template-columns: 1fr;
            }
        }

        /* Left Panel */
        .lv-sidebar-panel {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .lv-sidebar-header {
            padding: 2px 4px;
        }
        .lv-dvr-card {
            padding: 12px 14px;
            margin-bottom: 0;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .lv-dvr-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f3f4f6;
        }
        .lv-cameras {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .lv-cam-box {
            border: 1px solid #f3f4f6;
            background: #fafafa;
            border-radius: 6px;
            padding: 6px 8px;
            transition: all .15s ease;
        }
        .lv-cam-box:hover {
            background: #f3f4f6;
            border-color: #e5e7eb;
        }
        .lv-cam-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 8px;
            background: #fff;
            border: 1px solid #e5e7eb;
            cursor: pointer;
            user-select: none;
            transition: all .15s ease-in-out;
        }
        .lv-cam-card:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }
        .lv-cam-card:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 1px;
        }
        .lv-cam-card.is-streaming {
            background: #f0fdf4;
            border-color: #86efac;
            box-shadow: 0 0 0 1px #22c55e, 0 2px 6px rgba(34,197,94,0.12);
        }
        .lv-cam-card.is-starting {
            background: #fffbeb;
            border-color: #fde68a;
        }
        .lv-cam-card.lv-cam-disabled {
            opacity: .45;
            pointer-events: none;
            background: #f9fafb;
        }
        .lv-cam-card-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 0;
        }
        .lv-cam-play-btn {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #f1f5f9;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all .15s ease;
        }
        .lv-cam-card:hover .lv-cam-play-btn {
            background: #2563eb;
            color: #fff;
        }
        .lv-cam-card.is-streaming .lv-cam-play-btn {
            background: #22c55e;
            color: #fff;
        }
        .lv-cam-info-wrap {
            flex: 1;
            min-width: 0;
        }
        .lv-cam-title {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .lv-cam-meta-tags {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 3px;
            font-size: 11px;
            color: #64748b;
        }
        .lv-cam-online-label {
            font-size: 11px;
        }
        .lv-stream-pill {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            background: #f1f5f9;
            color: #475569;
            white-space: nowrap;
            transition: all .15s ease;
        }
        .lv-cam-card:hover .lv-stream-pill {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .lv-cam-card.is-streaming .lv-stream-pill {
            background: #dcfce7;
            color: #15803d;
        }
        .lv-cam-card.is-starting .lv-stream-pill {
            background: #fef3c7;
            color: #b45309;
        }
        @keyframes lv-pulse-beacon-anim {
            0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.8); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 5px rgba(255, 255, 255, 0); }
            100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0); }
        }
        .lv-pulse-beacon {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #fff;
            display: inline-block;
            animation: lv-pulse-beacon-anim 1.4s infinite;
        }
        .lv-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .lv-status-dot.is-online {
            background: #10b981;
            box-shadow: 0 0 0 2px rgba(16,185,129,0.2);
        }
        .lv-status-dot.is-offline {
            background: #ef4444;
        }
        .lv-cam-subactions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px dashed #e5e7eb;
        }
        .lv-quick-tools {
            display: flex;
            gap: 4px;
        }
        .btn-xs {
            padding: 3px 8px;
            font-size: 11px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #4b5563;
            cursor: pointer;
            transition: all .12s;
        }
        .btn-xs:hover {
            background: #f3f4f6;
            color: #111827;
        }
        .btn-alarm-trigger:hover {
            background: #fee2e2;
            color: #b91c1c;
            border-color: #fca5a5;
        }
        .lv-ptz {
            display: flex;
            gap: 3px;
            margin-right: auto;
        }
        .ptz-send {
            padding: 2px 6px;
            font-size: 10px;
        }

        /* Right Monitor Panel */
        .lv-monitor-panel {
            min-height: 480px;
        }
        .lv-empty-monitor {
            background: #fff;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 60px 24px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 420px;
        }
        .lv-empty-icon {
            width: 54px;
            height: 54px;
            color: #9ca3af;
            margin-bottom: 12px;
        }

        /* Video Grid */
        .lv-grid {
            display: none;
            gap: 14px;
            grid-template-columns: repeat(1, 1fr);
        }
        .lv-grid[data-cols="2"] { grid-template-columns: repeat(2, 1fr); }
        .lv-grid[data-cols="3"] { grid-template-columns: repeat(3, 1fr); }
        .lv-grid[data-cols="4"] { grid-template-columns: repeat(2, 1fr); }
        .lv-grid[data-cols="8"] { grid-template-columns: repeat(4, 1fr); }

        .lv-tile {
            background: #0f172a;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #334155;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .lv-tile video {
            width: 100%;
            aspect-ratio: 16 / 9;
            display: block;
            background: #000;
            object-fit: contain;
        }
        .lv-cap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 12px;
            background: #1e293b;
            color: #f8fafc;
            font-size: 12px;
            border-top: 1px solid #334155;
        }
        .lv-cap-title {
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .lv-cap-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .lv-cap .lv-cap-btn {
            background: #334155;
            color: #f1f5f9;
            border: 1px solid #475569;
            border-radius: 5px;
            padding: 3px 8px;
            font-size: 11px;
            cursor: pointer;
            transition: background .12s;
        }
        .lv-cap .lv-cap-btn:hover { background: #475569; }
        .lv-cap .lv-cap-stop:hover { background: #dc2626; border-color: #ef4444; }

        .lv-status {
            font-weight: 700;
            font-size: 11px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .lv-status::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }
        .lv-status-connected { color: #34d399; }
        .lv-status-connected::before { background: #10b981; box-shadow: 0 0 6px #10b981; }
        .lv-status-starting { color: #fbbf24; }
        .lv-status-starting::before { background: #f59e0b; }
        .lv-status-failed { color: #f87171; }
        .lv-status-failed::before { background: #ef4444; }

        @media (max-width: 900px) {
            .lv-grid[data-cols="3"],
            .lv-grid[data-cols="4"],
            .lv-grid[data-cols="8"] {
                grid-template-columns: repeat(1, 1fr);
            }
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
            const UNIT_ID = @json($selectedUnit?->id);
            const DVR_ID = @json($selectedDvr?->id);

            const STORAGE_PREFIX = 'cctv_lv_';

            function getStorageKey(suffix) {
                return STORAGE_PREFIX + (DVR_ID ? 'dvr_' + DVR_ID : 'unit_' + (UNIT_ID || 'global')) + '_' + suffix;
            }

            if (typeof window.Hls === 'undefined') {
                const alertEl = document.getElementById('lv-alert');
                if (alertEl) {
                    alertEl.textContent = 'hls.js gagal dimuat dari CDN (cdn.jsdelivr.net). Cek koneksi internet atau blokir CDN.';
                    alertEl.style.display = 'block';
                }
                return;
            }

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
            const emptyPlaceholder = document.getElementById('lv-empty-placeholder');
            const alertEl = document.getElementById('lv-alert');
            const modeEl = document.getElementById('lv-mode');
            const gridSelect = document.getElementById('lv-grid-select');
            const activeCountBadge = document.getElementById('lv-active-count');
            const startAllBtn = document.getElementById('lv-start-all');
            const stopAllBtn = document.getElementById('lv-stop-all');
            const quickStartBtn = document.getElementById('lv-quick-start');

            function showAlert(message) {
                alertEl.textContent = message;
                alertEl.style.display = 'block';
            }

            function hideAlert() {
                alertEl.style.display = 'none';
            }

            function updateActiveCountBadge() {
                const count = Object.keys(state.streams).length;
                if (activeCountBadge) {
                    activeCountBadge.textContent = count + ' Kamera Aktif';
                    activeCountBadge.style.background = count > 0 ? '#dcfce7' : '#e5e7eb';
                    activeCountBadge.style.color = count > 0 ? '#15803d' : '#374151';
                }
            }

            function resetCameraCard(cameraId) {
                const card = document.querySelector('.lv-cam-card[data-camera-id="' + cameraId + '"]');
                if (!card) return;

                card.classList.remove('is-streaming', 'is-starting');
                const pill = card.querySelector('.lv-stream-pill');
                if (pill) pill.textContent = 'Putar';

                const playIcon = card.querySelector('.lv-icon-idle-play');
                const pulse = card.querySelector('.lv-pulse-beacon');
                if (playIcon) playIcon.style.display = 'inline-block';
                if (pulse) pulse.style.display = 'none';
            }

            function setCameraCardStreaming(cameraId) {
                const card = document.querySelector('.lv-cam-card[data-camera-id="' + cameraId + '"]');
                if (!card) return;

                card.classList.remove('is-starting');
                card.classList.add('is-streaming');
                const pill = card.querySelector('.lv-stream-pill');
                if (pill) pill.textContent = '● LIVE';

                const playIcon = card.querySelector('.lv-icon-idle-play');
                const pulse = card.querySelector('.lv-pulse-beacon');
                if (playIcon) playIcon.style.display = 'none';
                if (pulse) pulse.style.display = 'inline-block';
            }

            function saveActiveStreamingState() {
                if (!UNIT_ID) return;
                const activeIds = Object.values(state.streams).map(s => s.cameraId).filter(Boolean);
                try {
                    localStorage.setItem(getStorageKey('active_cams'), JSON.stringify(activeIds));
                    localStorage.setItem(getStorageKey('is_streaming'), activeIds.length > 0 ? '1' : '0');
                } catch (e) {}
            }

            function isStreamingSavedActive() {
                if (!UNIT_ID) return false;
                try {
                    return localStorage.getItem(getStorageKey('is_streaming')) === '1';
                } catch (e) {
                    return false;
                }
            }

            function savePreferences() {
                try {
                    localStorage.setItem(STORAGE_PREFIX + 'pref_mode', modeEl.value);
                    localStorage.setItem(STORAGE_PREFIX + 'pref_grid', gridSelect.value);
                } catch (e) {}
            }

            function restorePreferences() {
                try {
                    const savedMode = localStorage.getItem(STORAGE_PREFIX + 'pref_mode');
                    if (savedMode && (savedMode === 'local' || savedMode === 'public')) {
                        modeEl.value = savedMode;
                        state.mode = savedMode;
                    }
                    const savedGrid = localStorage.getItem(STORAGE_PREFIX + 'pref_grid');
                    if (savedGrid) {
                        gridSelect.value = savedGrid;
                        state.grid = parseInt(savedGrid, 10) || 4;
                    }
                } catch (e) {}
            }

            function applyModeVisibility() {
                const isPublic = state.mode === 'public';
                document.querySelectorAll('.lv-cam-card').forEach(function (card) {
                    if (isPublic && card.dataset.public !== '1') {
                        card.classList.add('lv-cam-disabled');
                        card.title = 'Kamera tidak tersedia pada mode Public (DVR belum memiliki IP Public)';
                        const cId = parseInt(card.dataset.cameraId, 10);
                        for (const [sKey, s] of Object.entries(state.streams)) {
                            if (s.cameraId === cId) {
                                stopStream(sKey);
                            }
                        }
                    } else {
                        card.classList.remove('lv-cam-disabled');
                        card.title = 'Klik untuk langsung memutar siaran kamera ini';
                    }
                });
            }

            function updateGridCols() {
                const count = Object.keys(state.streams).length;
                if (count === 0) {
                    gridEl.style.display = 'none';
                    if (emptyPlaceholder) emptyPlaceholder.style.display = 'flex';
                    return;
                }

                gridEl.style.display = 'grid';
                if (emptyPlaceholder) emptyPlaceholder.style.display = 'none';

                let cols = 1;
                if (count === 2) cols = 2;
                else if (count === 3) cols = 3;
                else if (count === 4) cols = 2;
                else if (count > 4) cols = 4;
                gridEl.setAttribute('data-cols', String(cols));
            }

            function setStatus(streamKey, status) {
                const stream = state.streams[streamKey];
                if (!stream) return;
                stream.statusEl.textContent = status === 'connected' ? 'LIVE'
                    : status === 'starting' ? 'menyiapkan' : 'gagal';
                stream.statusEl.className = 'lv-status lv-status-' + status;
            }

            function addTile(cameraData, streamKey, cameraId) {
                const tile = document.createElement('div');
                tile.className = 'lv-tile';

                const video = document.createElement('video');
                video.autoplay = true;
                video.muted = true;
                video.playsInline = true;
                video.controls = true;

                const cap = document.createElement('div');
                cap.className = 'lv-cap';

                const titleSpan = document.createElement('span');
                titleSpan.className = 'lv-cap-title';
                titleSpan.innerHTML = 'CH ' + cameraData.channel + ' — ' + cameraData.name
                    + ' <small style="color:#94a3b8; font-weight:normal;">(' + cameraData.dvr + ')</small>';
                cap.appendChild(titleSpan);

                const actionsDiv = document.createElement('div');
                actionsDiv.className = 'lv-cap-actions';

                const statusEl = document.createElement('span');
                statusEl.className = 'lv-status lv-status-starting';
                statusEl.textContent = 'menyiapkan';
                actionsDiv.appendChild(statusEl);

                const fsBtn = document.createElement('button');
                fsBtn.className = 'lv-cap-btn';
                fsBtn.type = 'button';
                fsBtn.title = 'Layar Penuh';
                fsBtn.textContent = '⛶';
                fsBtn.addEventListener('click', function () {
                    if (!document.fullscreenElement) {
                        tile.requestFullscreen().catch(function () {});
                    } else {
                        document.exitFullscreen().catch(function () {});
                    }
                });
                actionsDiv.appendChild(fsBtn);

                const stopBtn = document.createElement('button');
                stopBtn.className = 'lv-cap-btn lv-cap-stop';
                stopBtn.type = 'button';
                stopBtn.title = 'Hentikan stream kamera ini';
                stopBtn.textContent = '✕ Stop';
                actionsDiv.appendChild(stopBtn);

                cap.appendChild(actionsDiv);

                tile.appendChild(video);
                tile.appendChild(cap);
                gridEl.appendChild(tile);

                const hls = new Hls({
                    liveSyncDurationCount: 3,
                    maxLiveSyncPlaybackRate: 1.5,
                    manifestLoadingMaxRetry: 10,
                    manifestLoadingRetryDelay: 2000,
                    manifestLoadingTimeOut: 10000,
                });

                hls.on(Hls.Events.ERROR, function (_e, data) {
                    if (data.fatal) {
                        switch (data.type) {
                            case Hls.ErrorTypes.NETWORK_ERROR:
                                hls.startLoad();
                                break;
                            case Hls.ErrorTypes.MEDIA_ERROR:
                                hls.recoverMediaError();
                                break;
                            default:
                                setStatus(streamKey, 'failed');
                                break;
                        }
                    }
                });
                video.addEventListener('loadeddata', function () {
                    setStatus(streamKey, 'connected');
                    video.play().catch(function () {});
                });

                const stream = {
                    hls: hls,
                    tile: tile,
                    statusEl: statusEl,
                    poller: null,
                    loaded: false,
                    cameraId: cameraId,
                };
                state.streams[streamKey] = stream;
                updateGridCols();

                const startLoad = function () {
                    const current = state.streams[streamKey];
                    if (!current || current.loaded) return;
                    current.loaded = true;
                    const src = '/hls/' + encodeURIComponent(streamKey) + '/index.m3u8';
                    if (Hls.isSupported()) {
                        hls.loadSource(src);
                        hls.attachMedia(video);
                        hls.on(Hls.Events.MANIFEST_PARSED, function () {
                            video.play().catch(function () {});
                        });
                    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                        video.src = src;
                        video.play().catch(function () {});
                    }
                };

                const poll = function () {
                    if (!state.streams[streamKey]) return;
                    fetch(URLS.status + '?stream_key=' + encodeURIComponent(streamKey), { headers: headers })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            setStatus(streamKey, data.status);
                            if (data.status === 'connected') {
                                startLoad();
                            }
                            if (data.status === 'failed') {
                                let msg = 'Stream CH ' + cameraData.channel + ' — ' + cameraData.name + ' gagal terhubung.';
                                if (state.mode === 'local') {
                                    msg += '\nTip: Jika Anda berada di luar jaringan lokal DVR kebun, coba ubah Mode ke "Public" pada pilihan di atas.';
                                }
                                if (data.error) {
                                    msg += '\n\n' + data.error;
                                }
                                showAlert(msg);
                            }
                        })
                        .catch(function () {});
                };
                stream.poller = setInterval(poll, 3000);
                poll();

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
                    if (stream.cameraId) {
                        resetCameraCard(stream.cameraId);
                    }
                    stream.hls.destroy();
                    stream.tile.remove();
                    delete state.streams[streamKey];
                    updateGridCols();
                }
                updateActiveCountBadge();
                saveActiveStreamingState();
            }

            function stopAllStreams() {
                try {
                    sessionStorage.setItem(getStorageKey('user_paused'), '1');
                } catch (e) {}
                Object.keys(state.streams).forEach(stopStream);
                document.querySelectorAll('.lv-cam-card').forEach(function (card) {
                    const cId = parseInt(card.dataset.cameraId, 10);
                    resetCameraCard(cId);
                });
                updateActiveCountBadge();
                saveActiveStreamingState();
            }

            async function startSingleStream(card) {
                const cameraId = parseInt(card.dataset.cameraId, 10);
                const channel = card.dataset.channel;
                const name = card.dataset.name;
                const dvr = card.dataset.dvr;

                // Jika sudah aktif streaming, klik kembali akan menghentikannya (toggle off)
                for (const [sKey, s] of Object.entries(state.streams)) {
                    if (s.cameraId === cameraId) {
                        stopStream(sKey);
                        return;
                    }
                }

                // Cek batas grid monitor
                const gridLimit = parseInt(gridSelect.value, 10) || 4;
                const currentCount = Object.keys(state.streams).length;
                if (currentCount >= gridLimit) {
                    showAlert('Batas tampilan grid (' + gridLimit + ' layar) telah tercapai. Hentikan kamera yang sedang tayang atau ubah pilihan Grid di toolbar.');
                    return;
                }

                hideAlert();
                card.classList.add('is-starting');
                const pill = card.querySelector('.lv-stream-pill');
                if (pill) pill.textContent = 'Menghubungkan...';

                try {
                    const response = await fetch(URLS.start, {
                        method: 'POST',
                        headers: headers,
                        body: JSON.stringify({ camera_id: cameraId, mode: state.mode }),
                    });

                    if (response.status === 403 || response.status === 404) {
                        showAlert('Akses ke kamera ini ditolak.');
                        resetCameraCard(cameraId);
                        return;
                    }

                    const data = await response.json();
                    if (!data.ok) {
                        showAlert(data.error || 'Stream gagal dimulai.');
                        resetCameraCard(cameraId);
                        return;
                    }

                    setCameraCardStreaming(cameraId);

                    if (state.streams[data.streamKey]) {
                        return;
                    }

                    addTile({ channel, name, dvr }, data.streamKey, cameraId);
                    updateActiveCountBadge();
                    saveActiveStreamingState();
                } catch (e) {
                    showAlert('Gagal terhubung ke server.');
                    resetCameraCard(cameraId);
                }
            }

            async function startAllStreams() {
                hideAlert();
                try {
                    sessionStorage.removeItem(getStorageKey('user_paused'));
                } catch (e) {}
                const gridLimit = parseInt(gridSelect.value, 10) || 4;
                const cards = Array.from(document.querySelectorAll('.lv-cam-card:not(.lv-cam-disabled)'));

                for (const card of cards) {
                    if (Object.keys(state.streams).length >= gridLimit) {
                        break;
                    }
                    const cId = parseInt(card.dataset.cameraId, 10);
                    const isRunning = Object.values(state.streams).some(s => s.cameraId === cId);
                    if (!isRunning && !card.classList.contains('is-starting')) {
                        await startSingleStream(card);
                    }
                }
            }

            // Event Listeners untuk Interaksi Otomatis Kamera
            document.querySelectorAll('.lv-cam-card').forEach(function (card) {
                card.addEventListener('click', function () {
                    try {
                        sessionStorage.removeItem(getStorageKey('user_paused'));
                    } catch (e) {}
                    startSingleStream(card);
                });
                card.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        try {
                            sessionStorage.removeItem(getStorageKey('user_paused'));
                        } catch (e) {}
                        startSingleStream(card);
                    }
                });
            });

            if (startAllBtn) {
                startAllBtn.addEventListener('click', function () {
                    startAllStreams();
                });
            }

            if (quickStartBtn) {
                quickStartBtn.addEventListener('click', function () {
                    startAllStreams();
                });
            }

            if (stopAllBtn) {
                stopAllBtn.addEventListener('click', stopAllStreams);
            }

            modeEl.addEventListener('change', function () {
                state.mode = modeEl.value;
                savePreferences();
                stopAllStreams();
                applyModeVisibility();
            });

            gridSelect.addEventListener('change', function () {
                state.grid = parseInt(gridSelect.value, 10) || 4;
                savePreferences();
                updateGridCols();
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

            // Initial Setup
            restorePreferences();
            applyModeVisibility();
            updateActiveCountBadge();

            const JUST_LOGGED_IN = @json($justLoggedIn ?? false);
            if (JUST_LOGGED_IN) {
                try {
                    sessionStorage.removeItem(getStorageKey('user_paused'));
                } catch (e) {}
            }

            const userPaused = (function () {
                try {
                    return sessionStorage.getItem(getStorageKey('user_paused')) === '1';
                } catch (e) {
                    return false;
                }
            })();

            // Otomatis putar siaran kamera saat halaman dimuat
            if (!userPaused) {
                let resumed = false;
                if (isStreamingSavedActive()) {
                    try {
                        const raw = localStorage.getItem(getStorageKey('active_cams'));
                        if (raw) {
                            const ids = JSON.parse(raw);
                            if (Array.isArray(ids) && ids.length > 0) {
                                ids.forEach(function (id) {
                                    const card = document.querySelector('.lv-cam-card[data-camera-id="' + id + '"]:not(.lv-cam-disabled)');
                                    if (card) {
                                        startSingleStream(card);
                                        resumed = true;
                                    }
                                });
                            }
                        }
                    } catch (e) {}
                }

                // Jika belum ada kamera yang tersimpan atau baru pertama login, putar semua kamera otomatis
                if (!resumed) {
                    startAllStreams();
                }
            }
        })();
    </script>
@endpush

@push('scripts')
    <script>
        (function () {
            const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const alertEl = document.getElementById('lv-alert');
            if (!alertEl) {
                return;
            }

            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
            };

            function show(message) {
                alertEl.textContent = message;
                alertEl.style.display = 'block';
            }

            document.querySelectorAll('.rec-restart').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    fetch(btn.dataset.url, {
                        method: 'POST',
                        headers: headers,
                        body: JSON.stringify({
                            camera_id: parseInt(btn.dataset.cameraId, 10),
                            mode: 'local',
                        }),
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (!data.ok) {
                                show(data.error || 'Rekaman gagal dimulai.');
                            } else {
                                location.reload();
                            }
                        })
                        .catch(function () { show('Gagal terhubung ke server.'); });
                });
            });

            document.querySelectorAll('.ptz-send').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const code = btn.dataset.code;
                    const action = code === 'Stop' ? 'stop' : 'start';
                    fetch(btn.dataset.url, {
                        method: 'POST',
                        headers: headers,
                        body: JSON.stringify({ action: action, code: code }),
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (!data.ok) {
                                show(data.error || 'Perintah PTZ gagal.');
                            }
                        })
                        .catch(function () { show('Gagal terhubung ke server.'); });
                });
            });
        })();
    </script>
@endpush