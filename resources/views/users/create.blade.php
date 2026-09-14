@extends('layouts.app')

@section('title', 'Tambah User — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <h1>Tambah User</h1>
    </div>

    <form class="card" method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        <div class="form-group">
            <label for="name">Nama</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required>
            @error('name') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                @error('email') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                @error('password') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="teknis" @selected(old('role', 'teknis') === 'teknis')>Teknis</option>
                    <option value="superadmin" @selected(old('role') === 'superadmin')>Superadmin</option>
                </select>
                @error('role') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="technical_group_id">Grup Teknis</label>
                <select id="technical_group_id" name="technical_group_id">
                    <option value="">— Pilih grup —</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}" @selected(old('technical_group_id') == $group->id)>{{ $group->nama }}</option>
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