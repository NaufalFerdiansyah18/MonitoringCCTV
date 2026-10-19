@extends('layouts.app')

@section('title', 'Alarm — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <div class="page-head-title-wrap">
            <h1>Pusat Alarm CCTV</h1>
            <p class="page-subtitle">Catatan kejadian sensor kamera (motion alarm & status koneksi offline).</p>
        </div>
    </div>

    <form class="filter-toolbar" method="GET" action="{{ route('admin.alarms.index') }}">
        <div class="filter-inputs">
            <div style="min-width: 220px;">
                <select id="kategori" name="kategori">
                    <option value="">— Semua Kategori —</option>
                    @foreach ($categories as $kategori)
                        <option value="{{ $kategori }}" @selected(request('kategori') === $kategori)>
                            {{ $kategori === 'pks' ? 'PKS' : ucfirst($kategori) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-secondary" type="submit">Filter</button>
            @if (request('kategori'))
                <a class="btn btn-secondary" href="{{ route('admin.alarms.index') }}">Reset</a>
            @endif
        </div>
    </form>

    @if ($alarms->isEmpty())
        <div class="card empty">
            <svg style="width:40px;height:40px;color:#94a3b8;margin-bottom:10px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M6 9a6 6 0 1 1 12 0c0 5 2 6 2 6H4s2-1 2-6"/><path d="M10.5 20a2 2 0 0 0 3 0"/>
            </svg>
            <p style="font-weight:600; color:#475569; margin-bottom:4px;">Tidak ada catatan alarm</p>
            <p style="font-size:13px; color:#94a3b8;">Semua perangkat kamera beroperasi normal tanpa peringatan aktif.</p>
        </div>
    @else
        <div class="card-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Kamera & Channel</th>
                        <th>Unit</th>
                        <th style="width: 110px;">Kategori</th>
                        <th style="width: 100px;">Tipe</th>
                        <th>Pesan Kejadian</th>
                        <th>Waktu Mulai</th>
                        <th>Waktu Selesai</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 140px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($alarms as $alarm)
                        <tr>
                            <td>
                                <strong style="color:var(--text-heading); font-weight:600;">CH {{ $alarm->camera->channel }}</strong>
                                <span style="color:var(--text-muted); font-size:12px; margin-left:4px;">— {{ $alarm->camera->nama_lokasi }}</span>
                            </td>
                            <td>
                                <span class="unit-code">{{ $alarm->camera->dvr->unit?->kode }}</span>
                                <span style="font-size:12.5px; margin-left:4px;">{{ $alarm->camera->dvr->unit?->nama }}</span>
                            </td>
                            <td>
                                <span class="badge badge-{{ strtolower($alarm->camera->dvr->unit?->kategori ?? 'kategori') }}">
                                    {{ $alarm->camera->dvr->unit?->kategori === 'pks' ? 'PKS' : ucfirst($alarm->camera->dvr->unit?->kategori ?? '') }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $alarm->type === 'offline' ? 'failed' : 'kategori' }}">
                                    {{ ucfirst($alarm->type) }}
                                </span>
                            </td>
                            <td>{{ $alarm->message ?? '—' }}</td>
                            <td><span style="font-size:12.5px; color:var(--text-muted);">{{ $alarm->started_at?->format('d M Y H:i:s') ?? '—' }}</span></td>
                            <td><span style="font-size:12.5px; color:var(--text-muted);">{{ $alarm->ended_at?->format('d M Y H:i:s') ?? '—' }}</span></td>
                            <td>
                                @if ($alarm->seen_at !== null)
                                    <span class="badge badge-stopped">Dilihat</span>
                                @else
                                    <span class="badge badge-unseen">Baru</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                @if ($alarm->seen_at === null)
                                    <form method="POST" action="{{ route('admin.alarms.seen', $alarm) }}">
                                        @csrf
                                        <button class="btn btn-secondary btn-xs" type="submit">Tandai Dilihat</button>
                                    </form>
                                @else
                                    <span style="color:#94a3b8; font-size:12px;">{{ $alarm->seen_at->format('d M H:i') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection