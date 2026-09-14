@extends('layouts.app')

@section('title', 'User — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <h1>Manajemen User</h1>
        <a class="btn btn-primary" href="{{ route('admin.users.create') }}">Tambah User</a>
    </div>

    @if ($users->isEmpty())
        <div class="card empty">Belum ada user.</div>
    @else
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Grup Teknis</th>
                        <th style="width:150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="badge {{ $user->isSuperadmin() ? 'badge-superadmin' : 'badge-teknis' }}">
                                    {{ $user->isSuperadmin() ? 'Superadmin' : 'Teknis' }}
                                </span>
                            </td>
                            <td>{{ $user->technicalGroup?->nama ?? '—' }}</td>
                            <td>
                                <a class="btn btn-secondary btn-xs" href="{{ route('admin.users.edit', $user) }}">Edit</a>
                                @if (! $user->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" style="display:inline;"
                                          onsubmit="return confirm('Hapus user {{ $user->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-xs" type="submit">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    @endif
@endsection