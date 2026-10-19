@extends('layouts.app')

@section('title', 'DVR — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <div class="page-head-title-wrap">
            <h1>Manajemen DVR</h1>
            <p class="page-subtitle">Kelola perangkat DVR (Digital Video Recorder), konfigurasi IP, dan integrasi kamera.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.dvrs.create') }}">
            <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
            </svg>
            Tambah DVR
        </a>
    </div>

    <form class="filter-toolbar" method="GET" action="{{ route('admin.dvrs.index') }}">
        <div class="filter-inputs">
            <div class="filter-input-wrap">
                <svg class="filter-search-icon" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama DVR atau alamat IP...">
            </div>

            <div style="min-width: 260px;">
                <select id="unit" name="unit">
                    <option value="">— Semua Unit Lokasi —</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected($selectedUnit === $unit->id)>{{ $unit->kode }} — {{ $unit->nama }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-secondary" type="submit">Filter</button>
            @if ($selectedUnit || request('search'))
                <a class="btn btn-secondary" href="{{ route('admin.dvrs.index') }}" title="Hapus Filter">Reset</a>
            @endif
        </div>
    </form>

    @if ($dvrs->isEmpty())
        <div class="card empty">
            <svg style="width:40px;height:40px;color:#94a3b8;margin-bottom:10px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="2" y="7" width="20" height="10" rx="2"/><path d="M8 7v10M16 7v10"/>
            </svg>
            <p style="font-weight:600; color:#475569; margin-bottom:4px;">Belum ada DVR yang terdaftar</p>
            <p style="font-size:13px; color:#94a3b8;">Silakan tambahkan DVR baru untuk menghubungkan kamera CCTV.</p>
        </div>
    @else
        <div class="card-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Nama DVR</th>
                        <th>Unit Terkait</th>
                        <th>IP Local</th>
                        <th>Port</th>
                        <th>IP Public</th>
                        <th style="width: 110px;">Kamera</th>
                        <th style="width: 250px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dvrs as $dvr)
                        <tr>
                            <td>
                                <strong style="color:var(--text-heading); font-weight:600;">{{ $dvr->nama }}</strong>
                            </td>
                            <td>
                                <span class="badge badge-kategori">{{ $dvr->unit?->nama ?? '—' }}</span>
                            </td>
                            <td>
                                <span class="unit-code">{{ $dvr->ip_local }}</span>
                            </td>
                            <td>{{ $dvr->port_local }}</td>
                            <td>
                                @if ($dvr->ip_public)
                                    <span class="unit-code">{{ $dvr->ip_public }}:{{ $dvr->port_public }}</span>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="counter-pill">{{ $dvr->cameras_count ?? $dvr->cameras->count() }} ch</span>
                            </td>
                            <td style="text-align: right;">
                                <div class="actions-group">
                                    <a class="btn btn-primary btn-xs" href="{{ route('dashboard', ['dvr' => $dvr->id]) }}" title="Streaming Langsung DVR ini">Liveview</a>
                                    <a class="btn btn-secondary btn-xs" href="{{ route('admin.dvrs.cameras.index', $dvr) }}">Kamera</a>
                                    <a class="btn btn-secondary btn-xs" href="{{ route('admin.dvrs.edit', $dvr) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.dvrs.destroy', $dvr) }}" style="display:inline;"
                                          onsubmit="return confirm('Hapus DVR {{ $dvr->nama }}? Semua kamera di dalamnya ikut terhapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-xs" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $dvrs->links() }}
    @endif
@endsection