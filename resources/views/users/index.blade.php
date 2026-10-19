@extends('layouts.app')

@section('title', 'User — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <div class="page-head-title-wrap">
            <h1>Manajemen User</h1>
            <p class="page-subtitle">Kelola akun pengguna, hak akses peran (Superadmin / Teknis), dan grup unit.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.users.create') }}">
            <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
            </svg>
            Tambah User
        </a>
    </div>

    {{-- Filter & Search Toolbar --}}
    <form class="card" method="GET" action="{{ route('admin.users.index') }}" style="margin-bottom: 20px; padding: 14px 18px;">
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 220px; position: relative;">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari berdasarkan nama atau email user..."
                       style="padding-left: 36px;">
                <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #94a3b8; pointer-events: none;"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </div>

            <div style="width: 170px;">
                <select name="role">
                    <option value="">Semua Peran</option>
                    <option value="superadmin" @selected(request('role') === 'superadmin')>Superadmin</option>
                    <option value="teknis" @selected(request('role') === 'teknis')>Teknis</option>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button class="btn btn-primary btn-sm" type="submit">
                    <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                    </svg>
                    Cari
                </button>
                @if (request()->hasAny(['search', 'role']) && (request('search') !== null || request('role') !== null))
                    <a class="btn btn-secondary btn-sm" href="{{ route('admin.users.index') }}">Reset</a>
                @endif
            </div>
        </div>
    </form>

    @if ($users->isEmpty())
        <div class="card empty">
            <svg style="width:40px;height:40px;color:#94a3b8;margin-bottom:10px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>
            </svg>
            @if (request()->hasAny(['search', 'role']) && (request('search') !== null || request('role') !== null))
                <p style="font-weight:600; color:#475569; margin-bottom:4px;">Tidak ditemukan user yang cocok dengan pencarian Anda</p>
                <p style="font-size:13px; color:#94a3b8; margin-bottom:12px;">Coba gunakan kata kunci lain atau bersihkan filter.</p>
                <a class="btn btn-secondary btn-sm" href="{{ route('admin.users.index') }}">Reset Pencarian</a>
            @else
                <p style="font-weight:600; color:#475569; margin-bottom:4px;">Belum ada user yang terdaftar</p>
                <a class="btn btn-primary btn-sm" href="{{ route('admin.users.create') }}" style="margin-top: 10px;">Tambah User Sekarang</a>
            @endif
        </div>
    @else
        <div class="card-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Nama Pengguna</th>
                        <th>Email</th>
                        <th style="width: 140px;">Role</th>
                        <th>Grup Teknis Unit</th>
                        <th style="width: 160px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #e0e7ff; color: #4338ca; font-weight: 700; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <strong style="color:var(--text-heading); font-weight:600; display: block;">{{ $user->name }}</strong>
                                        @if ($user->is(auth()->user()))
                                            <span style="font-size: 11px; color: #10b981; font-weight: 600;">(Akun Anda saat ini)</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="badge {{ $user->isSuperadmin() ? 'badge-superadmin' : 'badge-teknis' }}">
                                    {{ $user->isSuperadmin() ? 'Superadmin' : 'Teknis' }}
                                </span>
                            </td>
                            <td>
                                @if ($user->technicalGroup?->nama)
                                    <span class="counter-pill">{{ $user->technicalGroup->nama }}</span>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <div class="actions-group">
                                    <a class="btn btn-secondary btn-xs" href="{{ route('admin.users.edit', $user) }}" title="Edit data user">Edit</a>
                                    @if (! $user->is(auth()->user()))
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" style="display:inline;"
                                              onsubmit="return confirm('Hapus user {{ $user->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-xs" type="submit" title="Hapus user ini">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    @endif
@endsection