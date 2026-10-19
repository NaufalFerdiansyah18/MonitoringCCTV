@extends('layouts.app')

@section('title', 'Manajemen Unit — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <div class="page-head-title-wrap">
            <h1>Manajemen Unit</h1>
            <p class="page-subtitle">Daftar unit operasional (Biogas Plant, Kebun, PKS) dan asosiasi DVR CCTV.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.units.create') }}">
            <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
            </svg>
            Tambah Unit
        </a>
    </div>

    <!-- Filter & Search Toolbar -->
    <form class="filter-toolbar" method="GET" action="{{ route('admin.units.index') }}">
        <div class="filter-inputs">
            <div class="filter-input-wrap">
                <svg class="filter-search-icon" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode atau nama unit...">
            </div>

            <div style="min-width: 180px;">
                <select name="kategori">
                    <option value="">— Semua Kategori —</option>
                    @foreach (\App\Models\Unit::KATEGORI as $kat)
                        <option value="{{ $kat }}" @selected(request('kategori') === $kat)>
                            {{ $kat === 'pks' ? 'PKS' : ucfirst($kat) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button class="btn btn-secondary" type="submit">Filter</button>
            @if (request('search') || request('kategori'))
                <a class="btn btn-secondary" href="{{ route('admin.units.index') }}" title="Hapus Filter">Reset</a>
            @endif
        </div>
    </form>

    @if ($units->isEmpty())
        <div class="card empty">
            <svg style="width:40px;height:40px;color:#94a3b8;margin-bottom:10px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            <p style="font-weight:600; color:#475569; margin-bottom:4px;">Tidak ada unit yang ditemukan</p>
            <p style="font-size:13px; color:#94a3b8;">Silakan sesuaikan kata kunci pencarian atau tambahkan unit baru.</p>
        </div>
    @else
        <div class="card-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width: 120px;">Kode</th>
                        <th>Nama Unit</th>
                        <th style="width: 140px;">Kategori</th>
                        <th style="width: 150px;">Jumlah DVR</th>
                        <th style="width: 170px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($units as $unit)
                        <tr>
                            <td>
                                <span class="unit-code">{{ $unit->kode }}</span>
                            </td>
                            <td>
                                <strong style="color:var(--text-heading); font-weight:600;">{{ $unit->nama }}</strong>
                            </td>
                            <td>
                                <span class="badge badge-{{ strtolower($unit->kategori) }}">
                                    {{ $unit->kategori === 'pks' ? 'PKS' : ucfirst($unit->kategori) }}
                                </span>
                            </td>
                            <td>
                                <span class="counter-pill">
                                    <svg style="width:13px;height:13px;opacity:0.7;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="7" width="20" height="10" rx="2"/><path d="M8 7v10M16 7v10"/>
                                    </svg>
                                    {{ $unit->dvrs_count }} DVR
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="actions-group">
                                    <a class="btn btn-secondary btn-xs" href="{{ route('admin.units.edit', $unit) }}" title="Edit unit">
                                        <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                        </svg>
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.units.destroy', $unit) }}" style="display:inline;"
                                          onsubmit="return confirm('Hapus unit {{ $unit->nama }}? Seluruh DVR dan kamera di dalamnya ikut terhapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-xs" type="submit" title="Hapus unit">
                                            <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $units->links() }}
    @endif
@endsection