@extends('layouts.app')

@section('title', 'Tambah Unit — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <div class="page-head-title-wrap">
            <h1>Tambah Unit</h1>
            <p class="page-subtitle">Daftarkan unit operasional baru ke dalam sistem monitoring.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('admin.units.index') }}">← Kembali ke Daftar</a>
    </div>

    <form class="card" method="POST" action="{{ route('admin.units.store') }}" style="max-width: 680px;">
        @csrf

        <div class="form-row">
            <div class="form-group" style="max-width:220px;">
                <label for="kode">Kode Unit <span class="required-mark">*</span></label>
                <input type="text" id="kode" name="kode" value="{{ old('kode') }}" required placeholder="Contoh: BGS-01" autofocus>
                <p class="form-hint">Format unik, misal: PKS-01, BGS-01, KBN-01.</p>
                @error('kode') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="nama">Nama Unit <span class="required-mark">*</span></label>
                <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required placeholder="Contoh: Biogas Plant Tandun">
                <p class="form-hint">Nama lengkap unit, pabrik, atau kebun.</p>
                @error('nama') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-group">
            <label for="kategori">Kategori Unit <span class="required-mark">*</span></label>
            <select id="kategori" name="kategori" required>
                @foreach (\App\Models\Unit::KATEGORI as $kategori)
                    <option value="{{ $kategori }}" @selected(old('kategori') === $kategori)>{{ $kategori === 'pks' ? 'PKS (Pabrik Kelapa Sawit)' : ($kategori === 'biogas' ? 'Biogas Plant' : ($kategori === 'kebun' ? 'Kebun Kelapa Sawit' : ucfirst($kategori))) }}</option>
                @endforeach
            </select>
            <p class="form-hint">Kategori menentukan hak akses bagi user teknis (akun PKS, Biogas, atau Kebun).</p>
            @error('kategori') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="actions" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
            <button class="btn btn-primary" type="submit">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                Simpan Unit
            </button>
            <a class="btn btn-secondary" href="{{ route('admin.units.index') }}">Batal</a>
        </div>
    </form>
@endsection