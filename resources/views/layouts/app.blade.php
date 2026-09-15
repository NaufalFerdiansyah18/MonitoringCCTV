<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CCTV Monitoring')</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "Segoe UI", Roboto, Arial, sans-serif;
            background: #f5f7fa;
            color: #1f2937;
            min-height: 100vh;
        }

        .app { display: flex; min-height: 100vh; }

        .sidebar {
            width: 240px;
            background: #111827;
            color: #fff;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .sidebar-brand {
            padding: 20px 20px 18px;
            font-weight: 700;
            font-size: 16px;
            color: #fff;
            border-bottom: 1px solid #1f2937;
        }

        .sidebar-nav { flex: 1; padding: 14px 10px; display: flex; flex-direction: column; gap: 4px; }

        .sidebar-nav .nav-label {
            font-size: 11px; text-transform: uppercase; letter-spacing: 0.6px;
            color: #6b7280; padding: 14px 12px 6px;
        }

        .nav-item {
            display: flex; align-items: center; gap: 10px;
            color: #d1d5db; text-decoration: none;
            padding: 10px 12px; border-radius: 8px; font-size: 14px;
        }
        .nav-item:hover { background: #1f2937; color: #fff; }
        .nav-item.active { background: #2563eb; color: #fff; }

        .nav-icon { width: 18px; height: 18px; opacity: .8; }

        .sidebar-footer {
            padding: 16px 14px;
            border-top: 1px solid #1f2937;
        }
        .sidebar-footer .who { font-size: 13px; color: #9ca3af; line-height: 1.3; margin-bottom: 10px; }
        .sidebar-footer .who strong { color: #fff; display: block; }

        .btn-logout {
            width: 100%; padding: 8px; border: 1px solid #374151; border-radius: 8px;
            background: transparent; color: #d1d5db; font-size: 13px; cursor: pointer;
        }
        .btn-logout:hover { color: #fff; border-color: #6b7280; }

        .main { flex: 1; padding: 32px 32px 48px; min-width: 0; }

        .page-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; }
        .page-head h1 { font-size: 22px; color: #111827; }

        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 18px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .alert {
            padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 18px;
        }
        .alert-success { background: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        .error-text { color: #b91c1c; font-size: 12px; margin-top: 4px; }

        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
        th { color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.4px; }

        .badge {
            display: inline-block; padding: 4px 10px; border-radius: 999px;
            font-size: 12px; font-weight: 600;
        }
        .badge-superadmin { background: #ede9fe; color: #6d28d9; }
        .badge-teknis { background: #e0f2fe; color: #0369a1; }
        .badge-kategori { background: #f3f4f6; color: #374151; }

        label { display: block; font-size: 13px; color: #374151; margin-bottom: 6px; font-weight: 600; }
        .form-group { margin-bottom: 16px; }

        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%; padding: 11px 14px; border-radius: 8px;
            border: 1px solid #d1d5db; background: #fff; color: #111827; font-size: 14px;
        }
        input:focus, select:focus { outline: 2px solid #2563eb; border-color: transparent; }

        .form-row { display: flex; gap: 14px; }
        .form-row .form-group { flex: 1; }

        .checkboxes { display: flex; gap: 18px; padding-top: 4px; }
        .checkboxes label { display: flex; align-items: center; gap: 7px; font-weight: 500; margin-bottom: 0; cursor: pointer; }
        input[type="checkbox"] { width: 16px; height: 16px; }

        .actions { display: flex; gap: 10px; margin-top: 10px; }

        button, .btn {
            padding: 10px 18px; border: none; border-radius: 8px;
            font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
        .btn-secondary:hover { background: #e5e7eb; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-xs { padding: 6px 12px; font-size: 12px; }

        .grid-units { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; }
        .unit-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
            padding: 18px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .unit-card h3 { font-size: 16px; margin-bottom: 4px; }
        .unit-card p { font-size: 13px; color: #6b7280; }
        .unit-card .tag { margin-top: 10px; }

        .empty { text-align: center; color: #9ca3af; padding: 40px 0; font-size: 14px; }

        @media (max-width: 768px) {
            .app { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: static; }
            .sidebar-nav { flex-direction: row; flex-wrap: wrap; }
            .main { padding: 24px 16px 40px; }
        }
    </style>
</head>
<body>
    @auth
        <div class="app">
            <aside class="sidebar">
                <div class="sidebar-brand">CCTV Monitoring</div>

                <nav class="sidebar-nav">
                    <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/>
                            <rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>
                        </svg>
                        Dashboard
                    </a>

                    @if (auth()->user()->isSuperadmin())
                        <div class="nav-label">Manajemen</div>
                        <a class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>
                            </svg>
                            User
                        </a>
                        <a class="nav-item {{ request()->routeIs('admin.technical-groups.*') ? 'active' : '' }}" href="{{ route('admin.technical-groups.index') }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="9" cy="8" r="3"/><circle cx="17" cy="10" r="2.5"/>
                                <path d="M3 21c0-3 2.5-5 6-5s6 2 6 5"/><circle cx="19" cy="19" r="2"/>
                            </svg>
                            Grup Teknis
                        </a>

                        <div class="nav-label">Master Data</div>
                        <a class="nav-item {{ request()->routeIs('admin.units.*') ? 'active' : '' }}" href="{{ route('admin.units.index') }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                                <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                            </svg>
                            Unit
                        </a>
                        <a class="nav-item {{ request()->routeIs('admin.dvrs*') ? 'active' : '' }}" href="{{ route('admin.dvrs.index') }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="7" width="20" height="10" rx="2"/><path d="M8 7v10M16 7v10"/>
                            </svg>
                            DVR
                        </a>
                    @endif
                </nav>

                <div class="sidebar-footer">
                    <div class="who">
                        <strong>{{ auth()->user()->name }}</strong>
                        {{ auth()->user()->isSuperadmin() ? 'Superadmin' : 'Teknis' }}
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn-logout" type="submit">Logout</button>
                    </form>
                </div>
            </aside>

            <main class="main">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if ($errors->any() && ! request()->is('login'))
                    <div class="alert alert-error">
                        <ul style="padding-left: 18px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    @endauth
    @stack('scripts')
</body>
</html>