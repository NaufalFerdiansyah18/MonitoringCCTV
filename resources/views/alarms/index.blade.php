@extends('layouts.app')

@section('title', 'Alarm — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <div>
            <h1>Alarm</h1>
            <p style="color:#6b7280; font-size:13px; margin-top:4px;">
                Catatan kejadian dari kamera (manual &amp; offline).
            </p>
        </div>
    </div>

    <form class="card" method="GET" action="{{ route('admin.alarms.index') }}">
        <div class="form-row">
            <div class="form-group">
                <label for="kategori">Kategori</label>
                <select id="kategori" name="kategori">
                    <option value="">— Semua —</option>
                    @foreach ($categories as $kategori)
                        <option value="{{ $kategori }}" @selected(request('kategori') === $kategori)>
                            {{ ucfirst($kategori) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="display:flex; align-items:flex-end; margin-bottom:0;">
                <button class="btn btn-secondary" type="submit">Filter</button>
            </div>
        </div>
    </form>

    @if ($alarms->isEmpty())
        <div class="card empty">Belum ada alarm.</div>
    @else
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Kamera</th>
                        <th>Unit</th>
                        <th>Kategori</th>
                        <th>Tipe</th>
                        <th>Pesan</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th>Status</th>
                        <th style="width:170px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($alarms as $alarm)
                        <tr>
                            <td>CH {{ $alarm->camera->channel }} — {{ $alarm->camera->nama_lokasi }}</td>
                            <td>{{ $alarm->camera->dvr->unit?->kode }} — {{ $alarm->camera->dvr->unit?->nama }}</td>
                            <td>
                                <span class="badge badge-kategori">{{ ucfirst($alarm->camera->dvr->unit?->kategori) }}</span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $alarm->type === 'offline' ? 'failed' : 'kategori' }}">
                                    {{ ucfirst($alarm->type) }}
                                </span>
                            </td>
                            <td>{{ $alarm->message ?? '—' }}</td>
                            <td>{{ $alarm->started_at?->format('d M Y H:i:s') ?? '—' }}</td>
                            <td>{{ $alarm->ended_at?->format('d M Y H:i:s') ?? '—' }}</td>
                            <td>
                                @if ($alarm->seen_at !== null)
                                    <span class="badge badge-stopped">Dilihat</span>
                                @else
                                    <span class="badge badge-unseen">Baru</span>
                                @endif
                            </td>
                            <td>
                                @if ($alarm->seen_at === null)
                                    <form method="POST" action="{{ route('admin.alarms.seen', $alarm) }}">
                                        @csrf
                                        <button class="btn btn-secondary btn-xs" type="submit">Tandai Dilihat</button>
                                    </form>
                                @else
                                    <span style="color:#9ca3af; font-size:12px;">{{ $alarm->seen_at->format('d M H:i') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection