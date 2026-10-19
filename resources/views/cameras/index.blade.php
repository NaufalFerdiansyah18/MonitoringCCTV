@extends('layouts.app')

@section('title', 'Kamera — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <div class="page-head-title-wrap">
            <h1>Kamera DVR: {{ $dvr->nama }}</h1>
            <p class="page-subtitle">
                Unit: <strong>{{ $dvr->unit?->kode }} — {{ $dvr->unit?->nama }}</strong> | IP: <code>{{ $dvr->ip_local }}:{{ $dvr->port_local }}</code>
            </p>
        </div>
        <div style="display:flex; gap:10px;">
            <a class="btn btn-secondary" href="{{ route('admin.dvrs.index') }}">← Kembali ke DVR</a>
            <a class="btn btn-primary" href="{{ route('admin.dvrs.cameras.create', $dvr) }}">
                <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Tambah Kamera
            </a>
        </div>
    </div>

    @if ($cameras->isEmpty())
        <div class="card empty">
            <svg style="width:40px;height:40px;color:#94a3b8;margin-bottom:10px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                <circle cx="12" cy="13" r="4"></circle>
            </svg>
            <p style="font-weight:600; color:#475569; margin-bottom:4px;">Belum ada kamera pada DVR ini</p>
            <p style="font-size:13px; color:#94a3b8; margin-bottom:14px;">Tambahkan channel kamera untuk mulai monitoring siaran.</p>
            <a class="btn btn-primary btn-xs" href="{{ route('admin.dvrs.cameras.create', $dvr) }}">+ Tambah Kamera Sekarang</a>
        </div>
    @else
        <div class="card-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width: 100px;">Channel</th>
                        <th>Nama Lokasi</th>
                        <th style="width: 160px;">Kategori</th>
                        <th style="width: 130px;">Status</th>
                        <th style="width: 180px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cameras as $camera)
                        <tr>
                            <td>
                                <span class="badge" style="background:#e0e7ff; color:#3730a3; font-weight:700;">CH {{ $camera->channel }}</span>
                            </td>
                            <td>
                                <strong style="color:var(--text-heading); font-weight:600;">{{ $camera->nama_lokasi }}</strong>
                            </td>
                            <td>
                                @if ($camera->kategori)
                                    <span class="badge badge-kategori">{{ $camera->kategori }}</span>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($camera->is_online)
                                    <span class="badge badge-online">● Online</span>
                                @else
                                    <span class="badge badge-offline">● Offline</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <div class="actions-group">
                                    <a class="btn btn-secondary btn-xs" href="{{ route('admin.dvrs.cameras.edit', [$dvr, $camera]) }}" title="Edit Kamera">
                                        <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                        </svg>
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.dvrs.cameras.destroy', [$dvr, $camera]) }}" style="display:inline;"
                                          onsubmit="return confirm('Hapus kamera channel {{ $camera->channel }}?');">
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
    @endif
@endsection