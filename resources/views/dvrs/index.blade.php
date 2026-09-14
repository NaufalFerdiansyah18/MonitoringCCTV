@extends('layouts.app')

@section('title', 'DVR — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <h1>Manajemen DVR</h1>
        <a class="btn btn-primary" href="{{ route('admin.dvrs.create') }}">Tambah DVR</a>
    </div>

    <form class="card" method="GET" action="{{ route('admin.dvrs.index') }}" style="padding-bottom:14px;">
        <div class="form-group" style="margin-bottom:0;">
            <label for="unit">Filter Unit</label>
            <div class="form-row">
                <select id="unit" name="unit" style="max-width:320px;">
                    <option value="">— Semua Unit —</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected($selectedUnit === $unit->id)>{{ $unit->kode }} — {{ $unit->nama }}</option>
                    @endforeach
                </select>
                <button class="btn btn-secondary" type="submit">Terapkan</button>
            </div>
        </div>
    </form>

    @if ($dvrs->isEmpty())
        <div class="card empty">Belum ada DVR.</div>
    @else
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Unit</th>
                        <th>IP Local</th>
                        <th>Port</th>
                        <th>IP Public</th>
                        <th>Kamera</th>
                        <th style="width:200px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dvrs as $dvr)
                        <tr>
                            <td>{{ $dvr->nama }}</td>
                            <td>{{ $dvr->unit?->nama }}</td>
                            <td>{{ $dvr->ip_local }}</td>
                            <td>{{ $dvr->port_local }}</td>
                            <td>
                                @if ($dvr->ip_public)
                                    {{ $dvr->ip_public }}:{{ $dvr->port_public }}
                                @else
                                    <span style="color:#9ca3af;">—</span>
                                @endif
                            </td>
                            <td>{{ $dvr->cameras_count ?? $dvr->cameras->count() }}</td>
                            <td>
                                <a class="btn btn-secondary btn-xs" href="{{ route('admin.dvrs.cameras.index', $dvr) }}">Kamera</a>
                                <a class="btn btn-secondary btn-xs" href="{{ route('admin.dvrs.edit', $dvr) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.dvrs.destroy', $dvr) }}" style="display:inline;"
                                      onsubmit="return confirm('Hapus DVR {{ $dvr->nama }}? Semua kamera di dalamnya ikut terhapus.');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-xs" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $dvrs->links() }}
    @endif
@endsection