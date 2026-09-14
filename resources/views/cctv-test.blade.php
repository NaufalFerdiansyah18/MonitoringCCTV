<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CCTV RTSP Testing</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "Segoe UI", Roboto, Arial, sans-serif;
            background: #f5f7fa;
            color: #1f2937;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 48px 16px;
        }

        .container { width: 100%; max-width: 720px; }

        h1 { font-size: 24px; color: #111827; margin-bottom: 6px; font-weight: 700; }
        .subtitle { color: #6b7280; margin-bottom: 28px; font-size: 14px; }

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .card h2 { font-size: 14px; color: #111827; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 14px; }

        label { display: block; font-size: 13px; color: #374151; margin-bottom: 6px; font-weight: 600; }

        .form-row { display: flex; gap: 10px; }
        .form-row input {
            flex: 1;
            padding: 11px 14px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            font-size: 14px;
            font-family: Consolas, monospace;
        }
        .form-row input:focus { outline: 2px solid #2563eb; border-color: transparent; }
        .form-row input:disabled { background: #f3f4f6; color: #9ca3af; }

        .hint { margin-top: 8px; font-size: 12px; color: #9ca3af; }

        button {
            padding: 11px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .15s, transform .05s;
        }
        button:active { transform: scale(.98); }
        button:disabled { opacity: .55; cursor: not-allowed; }

        .btn-primary { background: #2563eb; color: #ffffff; }
        .btn-primary:hover { background: #1d4ed8; }

        .btn-danger { background: #dc2626; color: #ffffff; }
        .btn-danger:hover { background: #b91c1c; }

        .btn-secondary { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
        .btn-secondary:hover { background: #e5e7eb; }

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
        .badge-idle { background: #f3f4f6; color: #6b7280; }
        .badge-waiting { background: #fef3c7; color: #b45309; }
        .badge-connected { background: #d1fae5; color: #047857; }
        .badge-failed { background: #fee2e2; color: #b91c1c; }

        .status-detail {
            margin-top: 12px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            font-size: 12px;
            line-height: 1.5;
            white-space: pre-wrap;
            word-break: break-word;
            color: #b91c1c;
            max-height: 220px;
            overflow-y: auto;
            font-family: Consolas, monospace;
        }

        .video-wrap {
            position: relative;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
            aspect-ratio: 16 / 9;
        }
        .video-wrap video { width: 100%; height: 100%; display: block; background: #000; }

        .actions { display: flex; gap: 10px; margin-top: 14px; }
    </style>
</head>
<body>
    <main class="container">
        <h1>CCTV Monitoring</h1>
        <p class="subtitle">Masukkan URL RTSP kamera untuk menguji stream di browser.</p>

        <section class="card">
            <form id="rtsp-form">
                <label for="rtsp_url">URL RTSP</label>
                <div class="form-row">
                    <input type="text" id="rtsp_url" name="rtsp_url"
                           placeholder="rtsp://username:password@IP_ADDRESS:554/stream"
                           autocomplete="off" spellcheck="false" required>
                    <button type="submit" id="btn-test" class="btn-primary">Test CCTV</button>
                </div>
                <p class="hint">Format: rtsp://username:password@IP_ADDRESS:PORT/path</p>
            </form>
        </section>

        <section class="card">
            <h2>Connection Status</h2>
            <div id="status-badge" class="badge badge-idle">Idle</div>
            <pre id="status-detail" class="status-detail" hidden></pre>
        </section>

        <section class="card" id="player-card" hidden>
            <h2>Live CCTV</h2>
            <div class="video-wrap">
                <video id="video" controls autoplay muted playsinline></video>
            </div>
            <div class="actions">
                <button id="btn-stop" class="btn-danger">Stop</button>
                <button id="btn-again" class="btn-secondary">Test Again</button>
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

        let pollTimer = null;
        let hls = null;
        let currentUrl = '';

        function setBadge(state, detail) {
            els.badge.className = 'badge badge-' + state;
            els.badge.textContent = ({
                idle: 'Idle',
                waiting: 'Connecting...',
                connected: 'Connected',
                failed: 'Connection Failed',
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
            els.btnTest.textContent = loading ? 'Testing...' : 'Test CCTV';
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
            setBadge('waiting', 'Menjalankan FFmpeg... mohon tunggu.');

            let res;
            try {
                res = await fetch('/cctv/test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ rtsp_url: url }),
                });
            } catch (_) {
                setLoading(false);
                setBadge('failed', 'Tidak dapat menghubungi server. Pastikan Laravel sudah berjalan.');
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
                return; // network hiccup, retry
            }
            const data = await res.json().catch(() => ({}));

            switch (data.status) {
                case 'connected':
                    clearInterval(pollTimer);
                    setLoading(false);
                    setBadge('connected', 'CCTV berhasil terhubung. Menampilkan live stream.');
                    await initPlayer();
                    pollTimer = setInterval(checkDrop, 5000);
                    break;
                case 'starting':
                    setBadge('waiting', 'Menghubungkan ke CCTV...');
                    break;
                default:
                    clearInterval(pollTimer);
                    setLoading(false);
                    setBadge('failed', data.error || 'Gagal terhubung ke CCTV.');
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
            setBadge('idle', 'Stream dihentikan.');
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

        // Hentikan FFmpeg jika tab ditutup (best effort).
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