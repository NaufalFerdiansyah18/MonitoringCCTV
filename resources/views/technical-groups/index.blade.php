@extends('layouts.app')

@section('title', 'Grup Teknis — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <div class="page-head-title-wrap">
            <h1>Manajemen Grup Teknis</h1>
            <p class="page-subtitle">Atur pembagian kelompok teknis dan hak akses kategori unit kamera.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.technical-groups.create') }}">
            <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
            </svg>
            Tambah Grup
        </a>
    </div>

    {{-- Filter Toolbar --}}
    <form class="card" method="GET" action="{{ route('admin.technical-groups.index') }}" style="margin-bottom: 20px; padding: 14px 18px;">
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 240px; position: relative;">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari berdasarkan nama grup teknis..."
                       style="padding-left: 36px;">
                <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #94a3b8; pointer-events: none;"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </div>

            <div style="display: flex; gap: 8px;">
                <button class="btn btn-primary btn-sm" type="submit">
                    <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                    </svg>
                    Cari
                </button>
                @if (request()->filled('search'))
                    <a class="btn btn-secondary btn-sm" href="{{ route('admin.technical-groups.index') }}">Reset</a>
                @endif
            </div>
        </div>
    </form>

    @if ($groups->isEmpty())
        <div class="card empty">
            <svg style="width:40px;height:40px;color:#94a3b8;margin-bottom:10px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="9" cy="8" r="3"/><circle cx="17" cy="10" r="2.5"/>
                <path d="M3 21c0-3 2.5-5 6-5s6 2 6 5"/><circle cx="19" cy="19" r="2"/>
            </svg>
            @if (request()->filled('search'))
                <p style="font-weight:600; color:#475569; margin-bottom:4px;">Tidak ada grup yang sesuai dengan "{{ request('search') }}"</p>
                <a class="btn btn-secondary btn-sm" href="{{ route('admin.technical-groups.index') }}" style="margin-top: 8px;">Reset Pencarian</a>
            @else
                <p style="font-weight:600; color:#475569; margin-bottom:4px;">Belum ada grup teknis</p>
                <a class="btn btn-primary btn-sm" href="{{ route('admin.technical-groups.create') }}" style="margin-top: 10px;">Tambah Grup Sekarang</a>
            @endif
        </div>
    @else
        <div class="card-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Nama Grup</th>
                        <th>Kategori Unit yang Diizinkan</th>
                        <th style="width: 140px;">Jumlah User</th>
                        <th style="width: 160px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groups as $group)
                        <tr>
                            <td>
                                <strong style="color:var(--text-heading); font-weight:600;">{{ $group->nama }}</strong>
                            </td>
                            <td>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    @forelse ($group->unitCategoryRows as $kategori)
                                        <span class="badge badge-{{ strtolower($kategori->kategori) }}">
                                            @if (strtolower($kategori->kategori) === 'kebun') 🌿 Kebun
                                            @elseif (strtolower($kategori->kategori) === 'pks') 🏭 PKS
                                            @elseif (strtolower($kategori->kategori) === 'biogas') ⚡ Biogas
                                            @else {{ ucfirst($kategori->kategori) }}
                                            @endif
                                        </span>
                                    @empty
                                        <span style="color:#94a3b8; font-size:12px;">(Tidak ada kategori terpilih)</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <span class="counter-pill">{{ $group->users_count ?? 0 }} user</span>
                            </td>
                            <td style="text-align: right;">
                                <div class="actions-group">
                                    <a class="btn btn-secondary btn-xs" href="{{ route('admin.technical-groups.edit', $group) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.technical-groups.destroy', $group) }}" style="display:inline;"
                                          onsubmit="return confirm('Hapus grup {{ $group->nama }}? User di dalamnya akan kehilangan filter unit.');">
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

        {{ $groups->links() }}
    @endif
@endsection