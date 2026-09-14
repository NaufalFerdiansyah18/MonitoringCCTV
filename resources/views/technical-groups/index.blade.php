@extends('layouts.app')

@section('title', 'Grup Teknis — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <h1>Manajemen Grup Teknis</h1>
        <a class="btn btn-primary" href="{{ route('admin.technical-groups.create') }}">Tambah Grup</a>
    </div>

    @if ($groups->isEmpty())
        <div class="card empty">Belum ada grup teknis.</div>
    @else
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Nama Grup</th>
                        <th>Kategori Unit</th>
                        <th>Jumlah User</th>
                        <th style="width:150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groups as $group)
                        <tr>
                            <td>{{ $group->nama }}</td>
                            <td>
                                @forelse ($group->unitCategoryRows as $kategori)
                                    <span class="badge badge-kategori">{{ ucfirst($kategori->kategori) }}</span>
                                @empty
                                    <span style="color:#9ca3af;">—</span>
                                @endforelse
                            </td>
                            <td>{{ $group->users_count ?? 0 }}</td>
                            <td>
                                <a class="btn btn-secondary btn-xs" href="{{ route('admin.technical-groups.edit', $group) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.technical-groups.destroy', $group) }}" style="display:inline;"
                                      onsubmit="return confirm('Hapus grup {{ $group->nama }}? User di dalamnya kehilangan akses unit.');">
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

        {{ $groups->links() }}
    @endif
@endsection