@extends('layouts.app')

@section('title', 'Kamera — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <div>
            <h1>Kamera DVR: {{ $dvr->nama }}</h1>
            <p style="color:#6b7280; font-size:13px; margin-top:4px;">
                {{ $dvr->unit?->kode }} — {{ $dvr->unit?->nama }}
            </p>
        </div>
        <div>
            <a class="btn btn-secondary" href="{{ route('admin.dvrs.index') }}">Kembali ke DVR</a>
            <a class="btn btn-primary" href="{{ route('admin.dvrs.cameras.create', $dvr) }}">Tambah Kamera</a>
        </div>
    </div>

    @if ($cameras->isEmpty())
        <div class="card empty">Belum ada kamera pada DVR ini.</div>
    @else
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Channel</th>
                        <th>Nama Lokasi</th>
                        <th>Kategori</th>
                        <th style="width:160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cameras as $camera)
                        <tr>
                            <td>{{ $camera->channel }}</td>
                            <td>{{ $camera->nama_lokasi }}</td>
                            <td>
                                @if ($camera->kategori)
                                    <span class="badge badge-kategori">{{ $camera->kategori }}</span>
                                @else
                                    <span style="color:#9ca3af;">—</span>
                                @endif
                            </td>
                            <td>
                                <a class="btn btn-secondary btn-xs" href="{{ route('admin.dvrs.cameras.edit', [$dvr, $camera]) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.dvrs.cameras.destroy', [$dvr, $camera]) }}" style="display:inline;"
                                      onsubmit="return confirm('Hapus kamera channel {{ $camera->channel }}?');">
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
    @endif
@endsection