<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CCTV Monitoring')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --font-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-mono: 'JetBrains Mono', ui-monospace, monospace;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            --bg-page: #f8fafc;
            --card-bg: #ffffff;
            --card-border: #e2e8f0;
            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
            --sidebar-bg: #0b1120;
            --sidebar-border: rgba(255, 255, 255, 0.08);
            --radius-md: 10px;
            --radius-lg: 14px;
            --radius-xl: 18px;
            --shadow-subtle: 0 1px 3px rgba(15, 23, 42, 0.04), 0 4px 12px -2px rgba(15, 23, 42, 0.03);
            --shadow-card: 0 1px 3px rgba(15, 23, 42, 0.05), 0 8px 20px -4px rgba(15, 23, 42, 0.04);
            --shadow-primary: 0 4px 14px rgba(79, 70, 229, 0.3);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-sans);
            background: var(--bg-page);
            color: var(--text-body);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .app { display: flex; min-height: 100vh; }

        /* Sidebar Modern */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: #fff;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            border-right: 1px solid var(--sidebar-border);
            z-index: 40;
        }

        .sidebar-brand {
            padding: 24px 22px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--sidebar-border);
        }

        .brand-logo-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
            flex-shrink: 0;
        }

        .brand-logo-icon svg {
            width: 20px;
            height: 20px;
            color: #fff;
        }

        .brand-text-wrap {
            display: flex;
            flex-direction: column;
        }

        .brand-title {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: -0.01em;
            color: #ffffff;
            line-height: 1.2;
        }

        .brand-tag {
            font-size: 10px;
            font-weight: 700;
            color: #818cf8;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 2px;
        }

        .brand-live-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 8px #10b981;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.85); }
        }

        .sidebar-nav {
            flex: 1;
            padding: 18px 14px;
            display: flex;
            flex-direction: column;
            gap: 5px;
            overflow-y: auto;
        }

        .sidebar-nav .nav-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            color: #475569;
            padding: 16px 12px 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #94a3b8;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #ffffff;
            transform: translateX(2px);
        }

        .nav-item.active {
            background: var(--primary-gradient);
            color: #ffffff;
            font-weight: 600;
            box-shadow: var(--shadow-primary);
        }

        .nav-icon {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            opacity: 0.85;
            transition: opacity 0.18s ease;
        }

        .nav-item:hover .nav-icon,
        .nav-item.active .nav-icon {
            opacity: 1;
        }

        /* Nav Dropdown */
        .nav-dropdown {
            display: flex;
            flex-direction: column;
        }

        .nav-dropdown-trigger {
            display: flex;
            align-items: center;
        }

        .nav-dropdown-trigger .nav-item {
            flex: 1;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .nav-arrow-btn {
            background: transparent;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0 10px 10px 0;
            transition: all 0.18s ease;
        }

        .nav-arrow-btn:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #ffffff;
        }

        .nav-arrow-icon {
            width: 14px;
            height: 14px;
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .nav-dropdown.open .nav-arrow-icon {
            transform: rotate(180deg);
        }

        .nav-dropdown-menu {
            display: none;
            flex-direction: column;
            padding: 4px 0 6px 14px;
            gap: 3px;
            border-left: 1px solid rgba(255, 255, 255, 0.08);
            margin-left: 20px;
            margin-top: 4px;
        }

        .nav-dropdown.open .nav-dropdown-menu {
            display: flex;
        }

        .nav-sub-divider {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #475569;
            font-weight: 700;
            padding: 8px 10px 3px;
        }

        .nav-subitem {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #94a3b8;
            text-decoration: none;
            padding: 7px 11px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.15s ease;
        }

        .nav-subitem:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #ffffff;
        }

        .nav-subitem.active {
            background: rgba(99, 102, 241, 0.2);
            color: #818cf8;
            font-weight: 600;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .nav-subitem-bullet {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #475569;
            flex-shrink: 0;
            transition: background 0.15s ease;
        }

        .nav-subitem:hover .nav-subitem-bullet {
            background: #cbd5e1;
        }

        .nav-subitem.active .nav-subitem-bullet {
            background: #818cf8;
            box-shadow: 0 0 6px #818cf8;
        }

        .nav-subitem-badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.08);
            color: #94a3b8;
            margin-left: auto;
            font-weight: 600;
        }

        .nav-subitem.active .nav-subitem-badge {
            background: rgba(99, 102, 241, 0.35);
            color: #c7d2fe;
        }

        /* Sidebar Footer / User Profile */
        .sidebar-footer {
            padding: 16px 14px;
            border-top: 1px solid var(--sidebar-border);
            background: rgba(0, 0, 0, 0.15);
        }

        .user-profile-card {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 12px;
            padding: 8px 10px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.02em;
            flex-shrink: 0;
        }

        .user-meta {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-size: 13.5px;
            font-weight: 700;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role-pill {
            display: inline-block;
            font-size: 10.5px;
            font-weight: 600;
            color: #93c5fd;
            background: rgba(59, 130, 246, 0.18);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 9999px;
            padding: 1px 7px;
            margin-top: 2px;
        }

        .btn-logout {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 9px;
            background: rgba(255, 255, 255, 0.03);
            color: #94a3b8;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: all 0.15s ease;
        }

        .btn-logout:hover {
            color: #f87171;
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.3);
        }

        .btn-logout svg {
            width: 15px;
            height: 15px;
        }

        /* Main Content Layout */
        .main {
            flex: 1;
            padding: 32px 36px 48px;
            min-width: 0;
        }

        .page-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-head-title-wrap {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .page-head h1 {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--text-heading);
            line-height: 1.2;
        }

        .page-subtitle {
            font-size: 13.5px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Cards */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            padding: 22px 24px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-card);
            transition: box-shadow 0.2s ease;
        }

        .card-table-wrapper {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-card);
            margin-bottom: 20px;
        }

        /* Alerts */
        .alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 18px;
            border-radius: var(--radius-md);
            font-size: 13.5px;
            font-weight: 500;
            margin-bottom: 20px;
            animation: fadeIn 0.25s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .error-text {
            color: #dc2626;
            font-size: 12px;
            font-weight: 500;
            margin-top: 5px;
        }

        /* Modern Table */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13.5px;
        }

        th {
            background: #f8fafc;
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 13px 18px;
            border-bottom: 1px solid var(--card-border);
            text-align: left;
            white-space: nowrap;
        }

        td {
            padding: 14px 18px;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text-body);
            vertical-align: middle;
        }

        tbody tr {
            transition: background 0.15s ease;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11.5px;
            font-weight: 600;
            line-height: 1.2;
            white-space: nowrap;
        }

        .badge-biogas { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .badge-kebun { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-pks { background: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff; }
        .badge-superadmin { background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; }
        .badge-teknis { background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; }
        .badge-kategori { background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; }
        .badge-online { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .badge-offline { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .badge-recording { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .badge-stopped { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .badge-failed { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .badge-unseen { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }

        .unit-code {
            font-family: var(--font-mono);
            font-size: 12.5px;
            font-weight: 600;
            background: #f1f5f9;
            color: #0f172a;
            padding: 3px 8px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            display: inline-block;
        }

        .counter-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
            color: #334155;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 3px 9px;
            border-radius: 9999px;
            font-size: 12px;
        }

        .nav-badge {
            margin-left: auto;
            background: #ef4444;
            color: #fff;
            font-size: 11px;
            border-radius: 9999px;
            padding: 2px 7px;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
        }

        /* Buttons */
        button, .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 9px 16px;
            border: 1px solid transparent;
            border-radius: var(--radius-md);
            font-family: var(--font-sans);
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: #ffffff;
            border-color: rgba(79, 70, 229, 0.4);
            box-shadow: var(--shadow-primary);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(79, 70, 229, 0.4);
            color: #ffffff;
        }

        .btn-secondary {
            background: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
        }

        .btn-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .btn-danger:hover {
            background: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.25);
        }

        .btn-xs {
            padding: 5px 11px;
            font-size: 12px;
            border-radius: 7px;
        }

        .btn-icon {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
        }

        .actions-group {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Form Controls */
        label {
            display: block;
            font-size: 13px;
            color: var(--text-heading);
            margin-bottom: 6px;
            font-weight: 600;
        }

        .form-group { margin-bottom: 18px; }

        input[type="text"], input[type="email"], input[type="password"], input[type="number"], select {
            width: 100%;
            padding: 10px 14px;
            border-radius: var(--radius-md);
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: var(--text-heading);
            font-family: var(--font-sans);
            font-size: 13.5px;
            transition: all 0.15s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .form-hint {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
            line-height: 1.4;
        }

        .required-mark {
            color: #ef4444;
            font-weight: 700;
            margin-left: 2px;
        }

        .form-section-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
            margin: 22px 0 12px;
            padding-bottom: 6px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .password-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input-wrap input {
            padding-right: 42px;
        }

        .password-toggle-btn {
            position: absolute;
            right: 8px;
            background: transparent;
            border: none;
            cursor: pointer;
            color: #64748b;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: color 0.15s ease, background 0.15s ease;
        }

        .password-toggle-btn:hover {
            color: #1e293b;
            background: #f1f5f9;
        }

        .checkbox-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 10px;
            margin-top: 6px;
        }

        .checkbox-card {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 14px;
            cursor: pointer;
            transition: all 0.15s ease;
            background: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
            user-select: none;
        }

        .checkbox-card:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        .checkbox-card.is-checked {
            border-color: #6366f1;
            background: #eef2ff;
            color: #4338ca;
            font-weight: 600;
        }

        .quick-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }

        .quick-tag {
            font-size: 11.5px;
            padding: 4px 10px;
            border-radius: 9999px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .quick-tag:hover {
            background: #e0e7ff;
            color: #4338ca;
            border-color: #c7d2fe;
        }

        .channel-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 6px;
            margin-top: 6px;
        }

        @media (max-width: 600px) {
            .channel-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .channel-btn {
            padding: 9px 4px;
            text-align: center;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            transition: all 0.15s ease;
        }

        .channel-btn:hover:not(.is-occupied) {
            border-color: var(--primary);
            background: #eef2ff;
            color: var(--primary);
        }

        .channel-btn.is-active {
            background: var(--primary-gradient);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.35);
        }

        .channel-btn.is-occupied {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
            border-style: dashed;
            opacity: 0.65;
            position: relative;
        }

        .info-banner {
            padding: 11px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            line-height: 1.45;
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin-top: 8px;
        }

        .info-banner-blue {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .info-banner-amber {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .form-row {
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .form-row .form-group { flex: 1; }

        .checkboxes {
            display: flex;
            gap: 18px;
            padding-top: 4px;
        }

        .checkboxes label {
            display: flex;
            align-items: center;
            gap: 7px;
            font-weight: 500;
            margin-bottom: 0;
            cursor: pointer;
        }

        input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 14px;
        }

        /* Filter Toolbar Card */
        .filter-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            padding: 14px 18px;
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            margin-bottom: 18px;
            box-shadow: var(--shadow-subtle);
        }

        .filter-inputs {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            flex: 1;
        }

        .filter-input-wrap {
            position: relative;
            min-width: 240px;
        }

        .filter-input-wrap input {
            padding-left: 36px;
        }

        .filter-search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 15px;
            height: 15px;
            color: #94a3b8;
            pointer-events: none;
        }

        /* Grid Cards */
        .grid-units {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }

        .unit-card {
            background: #fff;
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-subtle);
            text-decoration: none;
            color: inherit;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
        }

        .unit-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-card);
            border-color: #cbd5e1;
        }

        .unit-card h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 4px;
        }

        .unit-card p {
            font-size: 13px;
            color: var(--text-muted);
        }

        .empty {
            text-align: center;
            color: #94a3b8;
            padding: 48px 24px;
            font-size: 14px;
        }

        /* ========================================================
           PAGINATION SYSTEM (PREVENTS SVG EXPANSION & LOOKS STUNNING)
           ======================================================== */
        .pagination-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 20px;
            padding: 14px 18px;
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-subtle);
        }

        .pagination-info {
            font-size: 13px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .pagination-info span {
            font-weight: 700;
            color: var(--text-heading);
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .pagination-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-body);
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .pagination-btn:hover:not(.disabled) {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: var(--text-heading);
        }

        .pagination-btn.disabled {
            color: #cbd5e1;
            background: #f8fafc;
            border-color: #e2e8f0;
            cursor: not-allowed;
            pointer-events: none;
        }

        .pagination-numbers {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .pagination-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-body);
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .pagination-number:hover:not(.active) {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: var(--text-heading);
        }

        .pagination-number.active {
            background: var(--primary-gradient);
            border-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.35);
        }

        .pagination-ellipsis {
            padding: 0 6px;
            font-size: 13px;
            color: #94a3b8;
        }

        /* CRITICAL: RESTRICT ALL SVG SIZE IN NAVIGATION / PAGINATION TO PREVENT EXPLODING ICONS */
        nav[role="navigation"] svg,
        .pagination-wrapper svg,
        .pagination-icon,
        svg.w-5,
        svg.h-5,
        .pagination svg {
            width: 16px !important;
            height: 16px !important;
            max-width: 16px !important;
            max-height: 16px !important;
            display: inline-block !important;
            vertical-align: middle !important;
            flex-shrink: 0 !important;
        }

        nav[role="navigation"] {
            margin-top: 18px;
        }

        @media (max-width: 860px) {
            .app { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: static; }
            .sidebar-nav { flex-direction: row; flex-wrap: wrap; }
            .main { padding: 20px 16px 36px; }
            .pagination-wrapper { flex-direction: column; align-items: stretch; text-align: center; }
            .pagination-info { justify-content: center; }
            .pagination-controls { justify-content: center; flex-wrap: wrap; }
        }
    </style>
</head>
<body>
    @auth
        <div class="app">
            <aside class="sidebar">
                <div class="sidebar-brand">
                    <div class="brand-logo-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="6" width="16" height="12" rx="2"/>
                            <circle cx="10" cy="12" r="3"/>
                            <path d="M18 10l4-3v10l-4-3"/>
                        </svg>
                    </div>
                    <div class="brand-text-wrap">
                        <span class="brand-title">CCTV Monitoring</span>
                        <span class="brand-tag"><span class="brand-live-dot"></span> LIVE SYSTEM</span>
                    </div>
                </div>

                <nav class="sidebar-nav">
                    <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/>
                            <rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>
                        </svg>
                        Dashboard
                    </a>

                    <a class="nav-item {{ request()->routeIs('recordings.*') ? 'active' : '' }}" href="{{ route('recordings.index') }}">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                        Rekaman
                    </a>

                    @if (auth()->user()->isSuperadmin())
                        <div class="nav-label">Manajemen</div>
                        <a class="nav-item {{ request()->routeIs('admin.alarms.*') ? 'active' : '' }}" href="{{ route('admin.alarms.index') }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 9a6 6 0 1 1 12 0c0 5 2 6 2 6H4s2-1 2-6"/><path d="M10.5 20a2 2 0 0 0 3 0"/>
                            </svg>
                            Alarm
                            @if ($unseenAlarmCount > 0)
                                <span class="nav-badge">{{ $unseenAlarmCount }}</span>
                            @endif
                        </a>
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
                        <div class="nav-dropdown {{ (request()->routeIs('admin.dvrs*') || (request()->routeIs('dashboard') && request()->filled('dvr'))) ? 'open' : '' }}" id="nav-dvr-dropdown">
                            <div class="nav-dropdown-trigger">
                                <a class="nav-item {{ (request()->routeIs('admin.dvrs.index') && !request()->routeIs('dashboard')) ? 'active' : '' }}" href="{{ route('admin.dvrs.index') }}">
                                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="7" width="20" height="10" rx="2"/><path d="M8 7v10M16 7v10"/>
                                    </svg>
                                    DVR
                                </a>
                                <button type="button" class="nav-arrow-btn" onclick="toggleNavDropdown('nav-dvr-dropdown', event)" title="Buka/Tutup Submenu DVR">
                                    <svg class="nav-arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </button>
                            </div>
                            <div class="nav-dropdown-menu">
                                <a class="nav-subitem {{ (request()->routeIs('admin.dvrs.index') && !request()->routeIs('dashboard')) ? 'active' : '' }}" href="{{ route('admin.dvrs.index') }}">
                                    <span>Kelola Data DVR</span>
                                </a>
                                @if (isset($sidebarDvrs) && $sidebarDvrs->isNotEmpty())
                                    <div class="nav-sub-divider">Liveview Kamera:</div>
                                    @foreach ($sidebarDvrs as $d)
                                        <a class="nav-subitem {{ (request()->routeIs('dashboard') && request()->query('dvr') == $d->id) ? 'active' : '' }}"
                                           href="{{ route('dashboard', ['dvr' => $d->id]) }}"
                                           title="Streaming DVR {{ $d->nama }} ({{ $d->cameras_count }} kamera)">
                                            <span class="nav-subitem-bullet"></span>
                                            <span class="nav-subitem-text">{{ $d->nama }}</span>
                                            <span class="nav-subitem-badge">{{ $d->cameras_count }} ch</span>
                                        </a>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        <div class="nav-label">Testing & Pengujian</div>
                        <a class="nav-item {{ request()->is('cctv-test*') ? 'active' : '' }}" href="/cctv-test" target="_blank" title="Uji streaming RTSP manual langsung di browser">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                            </svg>
                            Test RTSP Kamera ↗
                        </a>
                    @else
                        @if (isset($sidebarDvrs) && $sidebarDvrs->isNotEmpty())
                            <div class="nav-label">Liveview DVR</div>
                            @foreach ($sidebarDvrs as $d)
                                <a class="nav-item {{ (request()->routeIs('dashboard') && request()->query('dvr') == $d->id) ? 'active' : '' }}" href="{{ route('dashboard', ['dvr' => $d->id]) }}">
                                    <span class="nav-subitem-bullet"></span>
                                    <span class="nav-subitem-text">{{ $d->nama }}</span>
                                    <span class="badge" style="margin-left:auto; font-size:10px; background:rgba(255,255,255,0.08); color:#94a3b8;">{{ $d->cameras_count }} ch</span>
                                </a>
                            @endforeach
                        @endif
                    @endif
                </nav>

                <div class="sidebar-footer">
                    <div class="user-profile-card">
                        <div class="user-avatar">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                        <div class="user-meta">
                            <div class="user-name" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</div>
                            <span class="user-role-pill">
                                {{ auth()->user()->isSuperadmin() ? 'Superadmin' : (auth()->user()->technicalGroup?->nama ? 'Bagian ' . ucfirst(auth()->user()->technicalGroup->nama) : 'Teknis') }}
                            </span>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn-logout" type="submit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>
            </aside>

            <main class="main">
                @if (session('success'))
                    <div class="alert alert-success">
                        <svg style="width:18px;height:18px;flex-shrink:0;" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if ($errors->any() && ! request()->is('login'))
                    <div class="alert alert-error">
                        <svg style="width:18px;height:18px;flex-shrink:0;" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <ul style="padding-left: 14px;">
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
    <script>
        function toggleNavDropdown(id, e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            var el = document.getElementById(id);
            if (el) {
                el.classList.toggle('open');
            }
        }

        function togglePasswordVisibility(targetId, btn) {
            var input = document.getElementById(targetId);
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
                btn.title = 'Sembunyikan Password';
            } else {
                input.type = 'password';
                btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
                btn.title = 'Lihat Password';
            }
        }

        function toggleCheckboxCard(cardEl) {
            var cb = cardEl.querySelector('input[type="checkbox"]');
            if (cb) {
                cb.checked = !cb.checked;
                cardEl.classList.toggle('is-checked', cb.checked);
            }
        }
    </script>
    @stack('scripts')
</body>
</html>