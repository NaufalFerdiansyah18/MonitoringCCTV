<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CCTV Monitoring</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "Segoe UI", Roboto, Arial, sans-serif;
            background: #f5f7fa;
            color: #1f2937;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            padding: 32px;
            width: 100%;
            max-width: 380px;
        }

        .login-card h1 { font-size: 20px; color: #111827; margin-bottom: 4px; }
        .login-card .subtitle { color: #6b7280; font-size: 13px; margin-bottom: 22px; }

        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; color: #374151; margin-bottom: 6px; font-weight: 600; }

        input[type="email"], input[type="password"] {
            width: 100%; padding: 11px 14px; border-radius: 8px;
            border: 1px solid #d1d5db; background: #fff; color: #111827; font-size: 14px;
        }
        input:focus { outline: 2px solid #2563eb; border-color: transparent; }

        .remember { display: flex; align-items: center; gap: 7px; font-size: 13px; color: #374151; margin-bottom: 18px; cursor: pointer; }
        .remember input { width: 15px; height: 15px; }

        button {
            width: 100%; padding: 11px; border: none; border-radius: 8px;
            background: #2563eb; color: #fff; font-size: 14px; font-weight: 600; cursor: pointer;
        }
        button:hover { background: #1d4ed8; }

        .error-text {
            color: #b91c1c; font-size: 13px; margin-bottom: 14px;
            background: #fee2e2; border: 1px solid #fecaca; padding: 10px 14px; border-radius: 8px;
        }
    </style>
</head>
<body>
    <form class="login-card" method="POST" action="{{ route('login') }}">
        @csrf
        <h1>CCTV Monitoring</h1>
        <p class="subtitle">Masuk untuk mengakses monitoring CCTV.</p>

        @error('email')
            <div class="error-text">{{ $message }}</div>
        @enderror

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>

        <label class="remember">
            <input type="checkbox" name="remember" id="remember">
            Ingat saya
        </label>

        <button type="submit">Masuk</button>
    </form>
</body>
</html>