@extends('layouts.app')

@section('title', 'Edit User — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <h1>Edit User</h1>
    </div>

    <form class="card" method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="name">Nama</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
            @error('name') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="password">Password <small style="font-weight:400; color:#6b7280;">(kosongkan jika tidak diubah)</small></label>
                <input type="password" id="password" name="password">
                @error('password') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" @disabled($user->is(auth()->user())) required>
                    <option value="teknis" @selected(old('role', $user->role) === 'teknis')>Teknis</option>
                    <option value="superadmin" @selected(old('role', $user->role) === 'superadmin')>Superadmin</option>
                </select>
                @error('role') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="technical_group_id">Grup Teknis</label>
                <select id="technical_group_id" name="technical_group_id" @disabled($user->is(auth()->user()))>
                    <option value="">— Pilih grup —</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}" @selected(old('technical_group_id', $user->technical_group_id) == $group->id)>{{ $group->nama }}</option>
                    @endforeach
                </select>
                @error('technical_group_id') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="actions">
            <button class="btn btn-primary" type="submit">Simpan</button>
            <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">Batal</a>
        </div>
    </form>
@endsection