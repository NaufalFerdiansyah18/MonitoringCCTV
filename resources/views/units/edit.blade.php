@extends('layouts.app')

@section('title', 'Edit Unit — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <h1>Edit Unit</h1>
    </div>

    <form class="card" method="POST" action="{{ route('admin.units.update', $unit) }}">
        @csrf
        @method('PUT')

        <div class="form-row">
            <div class="form-group" style="max-width:220px;">
                <label for="kode">Kode</label>
                <input type="text" id="kode" name="kode" value="{{ old('kode', $unit->kode) }}" required>
                @error('kode') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="nama">Nama</label>
                <input type="text" id="nama" name="nama" value="{{ old('nama', $unit->nama) }}" required>
                @error('nama') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-group">
            <label for="kategori">Kategori</label>
            <select id="kategori" name="kategori" required>
                @foreach (\App\Models\Unit::KATEGORI as $kategori)
                    <option value="{{ $kategori }}" @selected(old('kategori', $unit->kategori) === $kategori)>{{ ucfirst($kategori) }}</option>
                @endforeach
            </select>
            @error('kategori') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="actions">
            <button class="btn btn-primary" type="submit">Simpan</button>
            <a class="btn btn-secondary" href="{{ route('admin.units.index') }}">Batal</a>
        </div>
    </form>
@endsection