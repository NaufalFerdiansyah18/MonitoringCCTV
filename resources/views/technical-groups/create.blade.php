@extends('layouts.app')

@section('title', 'Tambah Grup Teknis — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <h1>Tambah Grup Teknis</h1>
    </div>

    <form class="card" method="POST" action="{{ route('admin.technical-groups.store') }}">
        @csrf

        <div class="form-group">
            <label for="nama">Nama Grup</label>
            <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required
                   placeholder="contoh: tekpol, tanaman, listrik, mekanik">
            @error('nama') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Kategori Unit yang Diizinkan</label>
            <div class="checkboxes">
                @foreach ($kategoris as $kategori)
                    <label>
                        <input type="checkbox" name="kategoris[]" value="{{ $kategori }}"
                               @checked(in_array($kategori, old('kategoris', [])))>
                        {{ ucfirst($kategori) }}
                    </label>
                @endforeach
            </div>
            @error('kategoris') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="actions">
            <button class="btn btn-primary" type="submit">Simpan</button>
            <a class="btn btn-secondary" href="{{ route('admin.technical-groups.index') }}">Batal</a>
        </div>
    </form>
@endsection