@extends('layouts.app')

@section('title', 'Unit — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <h1>Manajemen Unit</h1>
        <a class="btn btn-primary" href="{{ route('admin.units.create') }}">Tambah Unit</a>
    </div>

    @if ($units->isEmpty())
        <div class="card empty">Belum ada unit.</div>
    @else
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Jumlah DVR</th>
                        <th style="width:170px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($units as $unit)
                        <tr>
                            <td>{{ $unit->kode }}</td>
                            <td>{{ $unit->nama }}</td>
                            <td><span class="badge badge-kategori">{{ ucfirst($unit->kategori) }}</span></td>
                            <td>{{ $unit->dvrs_count }}</td>
                            <td>
                                <a class="btn btn-secondary btn-xs" href="{{ route('admin.units.edit', $unit) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.units.destroy', $unit) }}" style="display:inline;"
                                      onsubmit="return confirm('Hapus unit {{ $unit->nama }}? Seluruh DVR dan kamera di dalamnya ikut terhapus.');">
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

        {{ $units->links() }}
    @endif
@endsection