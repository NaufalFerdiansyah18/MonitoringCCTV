@extends('layouts.app')

@section('title', 'Edit DVR — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <h1>Edit DVR</h1>
    </div>

    <form class="card" method="POST" action="{{ route('admin.dvrs.update', $dvr) }}">
        @csrf
        @method('PUT')

        <div class="form-row">
            <div class="form-group">
                <label for="unit_id">Unit</label>
                <select id="unit_id" name="unit_id" required>
                    <option value="">— Pilih unit —</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected(old('unit_id', $dvr->unit_id) == $unit->id)>{{ $unit->kode }} — {{ $unit->nama }}</option>
                    @endforeach
                </select>
                @error('unit_id') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="nama">Nama DVR</label>
                <input type="text" id="nama" name="nama" value="{{ old('nama', $dvr->nama) }}" required>
                @error('nama') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="ip_local">IP Local</label>
                <input type="text" id="ip_local" name="ip_local" value="{{ old('ip_local', $dvr->ip_local) }}" required>
                @error('ip_local') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group" style="max-width:140px;">
                <label for="port_local">Port Local</label>
                <input type="number" id="port_local" name="port_local" value="{{ old('port_local', $dvr->port_local) }}" required min="1" max="65535">
                @error('port_local') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="ip_public">IP Public <small style="font-weight:400; color:#6b7280;">(opsional)</small></label>
                <input type="text" id="ip_public" name="ip_public" value="{{ old('ip_public', $dvr->ip_public) }}" placeholder="Kosongkan jika tidak ada">
                @error('ip_public') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group" style="max-width:140px;">
                <label for="port_public">Port Public <small style="font-weight:400; color:#6b7280;">(opsional)</small></label>
                <input type="number" id="port_public" name="port_public" value="{{ old('port_public', $dvr->port_public) }}" min="1" max="65535">
                @error('port_public') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username', $dvr->username) }}" required autocomplete="off">
                @error('username') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="password">Password <small style="font-weight:400; color:#6b7280;">(biarkan kosong jika tidak diubah)</small></label>
                <input type="password" id="password" name="password" autocomplete="new-password" placeholder="••••••••">
                @error('password') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="actions">
            <button class="btn btn-primary" type="submit">Simpan</button>
            <a class="btn btn-secondary" href="{{ route('admin.dvrs.index') }}">Batal</a>
        </div>
    </form>
@endsection