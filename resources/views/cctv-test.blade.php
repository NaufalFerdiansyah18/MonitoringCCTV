<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CCTV RTSP Testing Tool — CCTV Monitoring</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #eef2ff;
            --bg-base: #f8fafc;
            --card-bg: #ffffff;
            --card-border: #e2e8f0;
            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
            --radius-md: 10px;
            --radius-lg: 14px;
            --shadow-subtle: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
            --shadow-card: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg-base);
            color: var(--text-body);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 40px 18px 60px;
        }

        .container { width: 100%; max-width: 820px; }

        .top-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            padding: 8px 14px;
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: var(--radius-md);
            transition: all 0.15s ease;
        }
        .back-link:hover {
            color: var(--text-heading);
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .page-badge {
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            background: #e0e7ff;
            color: #4338ca;
            padding: 4px 10px;
            border-radius: 999px;
        }

        h1 { font-size: 26px; color: var(--text-heading); margin-bottom: 6px; font-weight: 800; letter-spacing: -0.02em; }
        .subtitle { color: var(--text-muted); margin-bottom: 24px; font-size: 14.5px; }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-subtle);
        }

        .card h2 {
            font-size: 13.5px;
            color: var(--text-heading);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        label { display: block; font-size: 13px; color: var(--text-heading); margin-bottom: 6px; font-weight: 600; }

        .form-row { display: flex; gap: 10px; }
        .form-row input {
            flex: 1;
            padding: 12px 14px;
            border-radius: var(--radius-md);
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #111827;
            font-size: 14px;
            font-family: 'JetBrains Mono', Consolas, monospace;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .form-row input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }
        .form-row input:disabled { background: #f1f5f9; color: #94a3b8; }

        .hint { margin-top: 8px; font-size: 12px; color: var(--text-muted); }

        button {
            padding: 11px 22px;
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        button:active { transform: scale(.98); }
        button:disabled { opacity: .55; cursor: not-allowed; }

        .btn-primary { background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); color: #ffffff; box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25); }
        .btn-primary:hover:not(:disabled) { background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%); }

        .btn-danger { background: #ef4444; color: #ffffff; }
        .btn-danger:hover:not(:disabled) { background: #dc2626; }

        .btn-secondary { background: #f8fafc; color: var(--text-body); border: 1px solid var(--card-border); }
        .btn-secondary:hover:not(:disabled) { background: #e2e8f0; }

        /* Generator Box */
        .generator-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 12px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 16px;
            cursor: pointer;
            user-select: none;
            transition: all 0.15s ease;
        }
        .generator-toggle:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }

        .generator-panel {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-md);
            padding: 18px;
            margin-bottom: 18px;
            display: none;
        }
        .generator-panel.open {
            display: block;
        }

        .gen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }
        .gen-field label {
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        .gen-field input, .gen-field select {
            width: 100%;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 13px;
            background: #ffffff;
            font-family: inherit;
        }

        .brand-presets {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .preset-btn {
            font-size: 12px;
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: var(--text-body);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .preset-btn.active, .preset-btn:hover {
            border-color: var(--primary);
            background: #eef2ff;
            color: #4338ca;
            font-weight: 600;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
        }
        .badge::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }
        .badge-idle { background: #f1f5f9; color: #64748b; }
        .badge-waiting { background: #fef3c7; color: #b45309; }
        .badge-connected { background: #d1fae5; color: #047857; }
        .badge-failed { background: #fee2e2; color: #b91c1c; }

        .status-detail {
            margin-top: 12px;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 8px;
            padding: 14px;
            font-size: 12px;
            line-height: 1.5;
            white-space: pre-wrap;
            word-break: break-word;
            color: #9f1239;
            max-height: 220px;
            overflow-y: auto;
            font-family: 'JetBrains Mono', Consolas, monospace;
        }

        .video-wrap {
            position: relative;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            aspect-ratio: 16 / 9;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
        }
        .video-wrap video { width: 100%; height: 100%; display: block; background: #000; }

        .actions { display: flex; gap: 10px; margin-top: 14px; }
    </style>
</head>
<body>
    <main class="container">
        <div class="top-nav">
            <a href="{{ route('dashboard') }}" class="back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Kembali ke Dashboard
            </a>
            <span class="page-badge">Diagnostic Tool</span>
        </div>

        <h1>Uji Coba RTSP CCTV</h1>
        <p class="subtitle">Gunakan alat ini untuk memastikan stream RTSP DVR/Kamera dapat dikonversi dan diputar oleh browser sebelum didaftarkan.</p>

        {{-- URL Input & Generator --}}
        <section class="card">
            <h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
                Alamat RTSP Target
            </h2>

            <!-- Quick Generator Trigger -->
            <div class="generator-toggle" onclick="toggleGenerator()">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="font-size:16px;">⚡</span>
                    <strong style="font-size:13px; color:var(--text-heading);">Bantuan Buat URL RTSP (Dahua / Hikvision / Uniview)</strong>
                </div>
                <span id="gen-arrow" style="font-size:12px; color:var(--text-muted); font-weight:600;">Buka Generator ▼</span>
            </div>

            <!-- Generator Panel -->
            <div id="generator-panel" class="generator-panel">
                <div style="font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:8px;">1. Pilih Merek DVR / NVR:</div>
                <div class="brand-presets">
                    <button type="button" class="preset-btn active" onclick="setBrand('dahua', this)">Dahua / Imou</button>
                    <button type="button" class="preset-btn" onclick="setBrand('hikvision', this)">Hikvision / Ezviz</button>
                    <button type="button" class="preset-btn" onclick="setBrand('uniview', this)">Uniview (UNV)</button>
                    <button type="button" class="preset-btn" onclick="setBrand('generic', this)">Generic RTSP</button>
                </div>

                <div style="font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:8px;">2. Isi Parameter Koneksi:</div>
                <div class="gen-grid">
                    <div class="gen-field">
                        <label>IP / Host</label>
                        <input type="text" id="gen-ip" placeholder="192.168.1.108" oninput="buildRtspUrl()">
                    </div>
                    <div class="gen-field">
                        <label>Port RTSP</label>
                        <input type="number" id="gen-port" value="554" oninput="buildRtspUrl()">
                    </div>
                    <div class="gen-field">
                        <label>Channel (1-16)</label>
                        <input type="number" id="gen-ch" value="1" min="1" max="64" oninput="buildRtspUrl()">
                    </div>
                    <div class="gen-field">
                        <label>Username</label>
                        <input type="text" id="gen-user" placeholder="admin" oninput="buildRtspUrl()">
                    </div>
                    <div class="gen-field">
                        <label>Password</label>
                        <input type="password" id="gen-pass" placeholder="password" oninput="buildRtspUrl()">
                    </div>
                    <div class="gen-field">
                        <label>Tipe Stream</label>
                        <select id="gen-stream" onchange="buildRtspUrl()">
                            <option value="main">Main Stream (HD)</option>
                            <option value="sub" selected>Sub Stream (Ringan)</option>
                        </select>
                    </div>
                </div>

                <button type="button" class="btn-secondary" style="font-size:12.5px; padding:6px 14px;" onclick="applyGeneratedUrl()">
                    Terapkan ke Input URL RTSP ↵
                </button>
            </div>

            <form id="rtsp-form">
                <label for="rtsp_url">URL RTSP Kamera</label>
                <div class="form-row">
                    <input type="text" id="rtsp_url" name="rtsp_url"
                           placeholder="rtsp://admin:admin123@192.168.1.100:554/cam/realmonitor?channel=1&subtype=0"
                           autocomplete="off" spellcheck="false" required>
                    <button type="submit" id="btn-test" class="btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <polygon points="5 3 19 12 5 21 5 3"></polygon>
                        </svg>
                        Uji Stream
                    </button>
                </div>
                <p class="hint">Format lengkap: <code>rtsp://username:password@IP_ADDRESS:PORT/path_stream</code></p>
            </form>
        </section>

        {{-- Connection Status --}}
        <section class="card">
            <h2>Status Koneksi Stream</h2>
            <div id="status-badge" class="badge badge-idle">Idle (Siap Uji)</div>
            <pre id="status-detail" class="status-detail" hidden></pre>
        </section>

        {{-- Video Player --}}
        <section class="card" id="player-card" hidden>
            <h2>Preview Live Stream CCTV</h2>
            <div class="video-wrap">
                <video id="video" controls autoplay muted playsinline></video>
            </div>
            <div class="actions">
                <button id="btn-stop" class="btn-danger">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>
                    Hentikan Stream
                </button>
                <button id="btn-again" class="btn-secondary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                    Uji Ulang
                </button>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.17"></script>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const els = {
            form: document.getElementById('rtsp-form'),
            input: document.getElementById('rtsp_url'),
            btnTest: document.getElementById('btn-test'),
            badge: document.getElementById('status-badge'),
            detail: document.getElementById('status-detail'),
            playerCard: document.getElementById('player-card'),
            video: document.getElementById('video'),
            btnStop: document.getElementById('btn-stop'),
            btnAgain: document.getElementById('btn-again'),
        };

        let currentBrand = 'dahua';
        let pollTimer = null;
        let hls = null;
        let currentUrl = '';

        function toggleGenerator() {
            const panel = document.getElementById('generator-panel');
            const arrow = document.getElementById('gen-arrow');
            panel.classList.toggle('open');
            arrow.textContent = panel.classList.contains('open') ? 'Tutup Generator ▲' : 'Buka Generator ▼';
        }

        function setBrand(brand, btn) {
            currentBrand = brand;
            document.querySelectorAll('.brand-presets .preset-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            buildRtspUrl();
        }

        function buildRtspUrl() {
            const ip = document.getElementById('gen-ip').value.trim() || '192.168.1.100';
            const port = document.getElementById('gen-port').value.trim() || '554';
            const ch = document.getElementById('gen-ch').value.trim() || '1';
            const user = document.getElementById('gen-user').value.trim() || 'admin';
            const pass = document.getElementById('gen-pass').value.trim() || 'admin123';
            const streamType = document.getElementById('gen-stream').value;

            let path = '';
            if (currentBrand === 'dahua') {
                const subtype = streamType === 'sub' ? '1' : '0';
                path = `/cam/realmonitor?channel=${ch}&subtype=${subtype}`;
            } else if (currentBrand === 'hikvision') {
                const streamCode = streamType === 'sub' ? '02' : '01';
                path = `/Streaming/Channels/${ch}${streamCode}`;
            } else if (currentBrand === 'uniview') {
                const streamNum = streamType === 'sub' ? 's1' : 's0';
                path = `/unicast/c${ch}/${streamNum}/live`;
            } else {
                path = `/live/ch${ch}`;
            }

            const url = `rtsp://${encodeURIComponent(user)}:${encodeURIComponent(pass)}@${ip}:${port}${path}`;
            return url;
        }

        function applyGeneratedUrl() {
            const url = buildRtspUrl();
            els.input.value = url;
            els.input.focus();
        }

        function setBadge(state, detail) {
            els.badge.className = 'badge badge-' + state;
            els.badge.textContent = ({
                idle: 'Idle (Siap Uji)',
                waiting: 'Menghubungkan ke CCTV...',
                connected: 'Stream Terhubung (Live)',
                failed: 'Koneksi Gagal',
            })[state] || state;

            if (detail) {
                els.detail.textContent = detail;
                els.detail.hidden = false;
            } else {
                els.detail.hidden = true;
            }
        }

        function setLoading(loading) {
            els.btnTest.disabled = loading;
            els.btnTest.innerHTML = loading
                ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Menguji...'
                : '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg> Uji Stream';
            els.input.disabled = loading;
        }

        function stopPlayer() {
            if (hls) { hls.destroy(); hls = null; }
            els.video.pause();
            els.video.removeAttribute('src');
            els.video.load();
            els.playerCard.hidden = true;
        }

        async function initPlayer() {
            stopPlayer();
            const src = '/hls/stream.m3u8';

            if (Hls.isSupported()) {
                hls = new Hls({ liveSyncDurationCount: 3, maxLiveSyncPlaybackRate: 1.5 });
                hls.loadSource(src);
                hls.attachMedia(els.video);
                hls.on(Hls.Events.ERROR, (_e, data) => {
                    if (data.fatal) {
                        setBadge('failed', 'Video error: ' + data.type + ' / ' + data.details);
                    }
                });
            } else if (els.video.canPlayType('application/vnd.apple.mpegurl')) {
                els.video.src = src; // Safari native HLS
            }

            els.playerCard.hidden = false;
            els.video.play().catch(() => {});
        }

        async function startTest(url) {
            currentUrl = url;
            setLoading(true);
            stopPlayer();
            setBadge('waiting', 'Menjalankan FFmpeg... Mohon tunggu beberapa detik.');

            let res;
            try {
                res = await fetch('/cctv/test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ rtsp_url: url }),
                });
            } catch (_) {
                setLoading(false);
                setBadge('failed', 'Tidak dapat menghubungi server. Pastikan aplikasi Laravel sedang aktif.');
                return;
            }

            const data = await res.json().catch(() => ({}));

            if (!res.ok || !data.ok) {
                setLoading(false);
                setBadge('failed', data.error || 'Terjadi kesalahan saat memulai stream.');
                return;
            }

            pollTimer = setInterval(pollStatus, 1500);
        }

        async function pollStatus() {
            let res;
            try {
                res = await fetch('/cctv/status');
            } catch (_) {
                return;
            }
            const data = await res.json().catch(() => ({}));

            switch (data.status) {
                case 'connected':
                    clearInterval(pollTimer);
                    setLoading(false);
                    setBadge('connected', 'CCTV RTSP berhasil diverifikasi! Live stream kini berjalan.');
                    await initPlayer();
                    pollTimer = setInterval(checkDrop, 5000);
                    break;
                case 'starting':
                    setBadge('waiting', 'Menghubungkan ke RTSP Kamera via FFmpeg...');
                    break;
                default:
                    clearInterval(pollTimer);
                    setLoading(false);
                    setBadge('failed', data.error || 'Gagal terhubung ke CCTV. Periksa kembali IP, port, atau kredensial RTSP.');
                    break;
            }
        }

        async function checkDrop() {
            let res;
            try {
                res = await fetch('/cctv/status');
            } catch (_) {
                return;
            }
            const data = await res.json().catch(() => ({}));
            if (data.status === 'failed') {
                clearInterval(pollTimer);
                setBadge('failed', data.error);
                stopPlayer();
            }
        }

        async function doStop() {
            clearInterval(pollTimer);
            stopPlayer();
            setBadge('idle', 'Stream dihentikan oleh pengguna.');
            setLoading(false);
            try {
                await fetch('/cctv/stop', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                });
            } catch (_) {}
            els.input.value = currentUrl;
            els.input.focus();
        }

        els.form.addEventListener('submit', (e) => {
            e.preventDefault();
            const url = els.input.value.trim();
            if (url) startTest(url);
        });

        els.btnStop.addEventListener('click', doStop);

        els.btnAgain.addEventListener('click', async () => {
            clearInterval(pollTimer);
            stopPlayer();
            try {
                await fetch('/cctv/stop', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                });
            } catch (_) {}
            await startTest(currentUrl);
        });

        window.addEventListener('beforeunload', () => {
            if (currentUrl) {
                const fd = new FormData();
                fd.append('_token', csrfToken);
                navigator.sendBeacon('/cctv/stop', fd);
            }
        });
    </script>
</body>
</html>