@extends('layouts.app')

@section('title', 'Tambah Kamera — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <h1>Tambah Kamera — {{ $dvr->nama }}</h1>
    </div>

    <form class="card" method="POST" action="{{ route('admin.dvrs.cameras.store', $dvr) }}">
        @csrf

        <div class="form-row">
            <div class="form-group" style="max-width:140px;">
                <label for="channel">Channel</label>
                <input type="number" id="channel" name="channel" value="{{ old('channel') }}" required min="1" max="16">
                @error('channel') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="nama_lokasi">Nama Lokasi</label>
                <input type="text" id="nama_lokasi" name="nama_lokasi" value="{{ old('nama_lokasi') }}" required
                       placeholder="contoh: Crh Timbangan, Rebusan">
                @error('nama_lokasi') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-group">
            <label for="kategori">Kategori <small style="font-weight:400; color:#6b7280;">(opsional, teks bebas)</small></label>
            <input type="text" id="kategori" name="kategori" value="{{ old('kategori') }}"
                   placeholder="contoh: PKS, Bioglas, Timbangan">
            @error('kategori') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="actions">
            <button class="btn btn-primary" type="submit">Simpan</button>
            <a class="btn btn-secondary" href="{{ route('admin.dvrs.cameras.index', $dvr) }}">Batal</a>
        </div>
    </form>
@endsection