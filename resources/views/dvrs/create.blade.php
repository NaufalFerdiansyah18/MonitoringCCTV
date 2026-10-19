@extends('layouts.app')

@section('title', 'Tambah DVR — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <div class="page-head-title-wrap">
            <h1>Tambah DVR</h1>
            <p class="page-subtitle">Daftarkan perangkat perekam video digital dan parameter koneksi RTSP.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('admin.dvrs.index') }}">← Kembali ke Daftar</a>
    </div>

    <form class="card" method="POST" action="{{ route('admin.dvrs.store') }}" style="max-width: 740px;">
        @csrf

        <div class="form-section-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="7" width="20" height="10" rx="2"></rect>
                <path d="M8 7v10M16 7v10"></path>
            </svg>
            1. Unit & Identitas Perangkat
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="unit_id">Unit Operasional <span class="required-mark">*</span></label>
                <select id="unit_id" name="unit_id" required autofocus>
                    <option value="">— Pilih unit lokasi —</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>{{ $unit->kode }} — {{ $unit->nama }} ({{ ucfirst($unit->kategori) }})</option>
                    @endforeach
                </select>
                <p class="form-hint">Pilih unit (PKS, Biogas, atau Kebun) tempat DVR ini berada.</p>
                @error('unit_id') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="nama">Nama DVR <span class="required-mark">*</span></label>
                <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required placeholder="Contoh: DVR Pabrik Utama">
                <p class="form-hint">Label nama untuk membedakan DVR.</p>
                @error('nama') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-section-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="2" y1="12" x2="22" y2="12"></line>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
            </svg>
            2. Konfigurasi Jaringan & Port RTSP
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="ip_local">IP Local (Intranet) <span class="required-mark">*</span></label>
                <input type="text" id="ip_local" name="ip_local" value="{{ old('ip_local') }}" required placeholder="Contoh: 192.168.1.10">
                <p class="form-hint">Alamat IP perangkat di jaringan lokal.</p>
                @error('ip_local') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group" style="max-width:160px;">
                <label for="port_local">Port Local <span class="required-mark">*</span></label>
                <input type="number" id="port_local" name="port_local" value="{{ old('port_local', 554) }}" required min="1" max="65535">
                <p class="form-hint">Default RTSP: 554.</p>
                @error('port_local') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="ip_public">IP Public / Domain <small style="font-weight:400; color:#6b7280;">(Opsional)</small></label>
                <input type="text" id="ip_public" name="ip_public" value="{{ old('ip_public') }}" placeholder="Contoh: 203.0.113.10">
                <p class="form-hint">Isi jika DVR bisa diakses dari luar kantor / internet publik.</p>
                @error('ip_public') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group" style="max-width:160px;">
                <label for="port_public">Port Public <small style="font-weight:400; color:#6b7280;">(Opsional)</small></label>
                <input type="number" id="port_public" name="port_public" value="{{ old('port_public') }}" min="1" max="65535" placeholder="Contoh: 38001">
                <p class="form-hint">Port forwarding eksternal.</p>
                @error('port_public') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-section-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            3. Kredensial Autentikasi DVR
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="username">Username DVR <span class="required-mark">*</span></label>
                <input type="text" id="username" name="username" value="{{ old('username', 'admin') }}" required autocomplete="off" placeholder="admin">
                <p class="form-hint">Username akun RTSP DVR (biasanya admin).</p>
                @error('username') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="password">Password DVR <span class="required-mark">*</span></label>
                <div class="password-input-wrap">
                    <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="Masukkan password DVR">
                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', this)" title="Lihat Password">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <p class="form-hint">Password RTSP DVR (tersimpan terenkripsi aman).</p>
                @error('password') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="actions" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
            <button class="btn btn-primary" type="submit">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                Simpan DVR
            </button>
            <a class="btn btn-secondary" href="{{ route('admin.dvrs.index') }}">Batal</a>
        </div>
    </form>
@endsection