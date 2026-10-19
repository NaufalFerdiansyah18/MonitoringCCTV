@extends('layouts.app')

@section('title', 'Tambah User — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <div class="page-head-title-wrap">
            <h1>Tambah Pengguna Baru</h1>
            <p class="page-subtitle">Daftarkan akun pengguna sistem dan tentukan hak akses peran serta unit kerja.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">
            Kembali ke Daftar
        </a>
    </div>

    <form class="card" method="POST" action="{{ route('admin.users.store') }}" style="max-width: 720px;">
        @csrf

        <div class="form-section-title">
            <span class="step-num">1</span> Informasi Akun Pengguna
        </div>

        <div class="form-group">
            <label for="name">Nama Lengkap <span class="required-mark">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Contoh: Operator PKS Mandau" required autofocus>
            <div class="form-hint">Nama personel atau nama posisi yang bertugas memantau kamera.</div>
            @error('name') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Alamat Email <span class="required-mark">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="operator.pks@perusahaan.co.id" required>
                <div class="form-hint">Digunakan sebagai identitas saat login ke sistem.</div>
                @error('email') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="password">Password <span class="required-mark">*</span></label>
                <div class="password-input-wrap">
                    <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6">
                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', this)" title="Lihat/Sembunyikan Password">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <div class="form-hint">Gunakan kombinasi kata sandi yang aman.</div>
                @error('password') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-section-title" style="margin-top: 10px;">
            <span class="step-num">2</span> Peran & Hak Akses
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="role">Peran Sistem (Role) <span class="required-mark">*</span></label>
                <select id="role" name="role" required onchange="handleRoleChange(this.value)">
                    <option value="teknis" @selected(old('role', 'teknis') === 'teknis')>Teknis (Petugas Unit / Kios)</option>
                    <option value="superadmin" @selected(old('role') === 'superadmin')>Superadmin (Akses Penuh)</option>
                </select>
                <div class="form-hint">Tentukan tingkat wewenang akun ini di dalam sistem.</div>
                @error('role') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group" id="group-select-wrap">
                <label for="technical_group_id">Grup Teknis Unit</label>
                <select id="technical_group_id" name="technical_group_id">
                    <option value="">— Pilih grup unit —</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}" @selected(old('technical_group_id') == $group->id)>
                            {{ $group->nama }}
                        </option>
                    @endforeach
                </select>
                <div class="form-hint" id="group-hint">Menentukan unit kamera yang berhak diakses oleh akun ini.</div>
                @error('technical_group_id') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div id="role-info-banner" class="info-banner" style="margin-top: 10px;">
            <strong>ℹ️ Catatan Peran:</strong> <span id="role-info-text">Akun Teknis dapat memantau liveview kamera unit yang terdaftar pada grup teknis yang dipilih.</span>
        </div>

        <div class="actions" style="margin-top: 24px;">
            <button class="btn btn-primary" type="submit">
                <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Simpan Pengguna
            </button>
            <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">Batal</a>
        </div>
    </form>

    <script>
        function handleRoleChange(role) {
            const groupSelect = document.getElementById('technical_group_id');
            const groupWrap = document.getElementById('group-select-wrap');
            const groupHint = document.getElementById('group-hint');
            const infoText = document.getElementById('role-info-text');

            if (role === 'superadmin') {
                groupSelect.value = '';
                groupSelect.disabled = true;
                groupSelect.style.opacity = '0.6';
                groupHint.textContent = 'Tidak diperlukan (Superadmin otomatis dapat mengakses seluruh unit).';
                infoText.textContent = 'Superadmin memiliki akses penuh ke manajemen Unit, DVR, Kamera, Alarm, User, dan Test RTSP.';
            } else {
                groupSelect.disabled = false;
                groupSelect.style.opacity = '1';
                groupHint.textContent = 'Menentukan unit kamera yang berhak diakses oleh akun ini.';
                infoText.textContent = 'Akun Teknis dapat memantau liveview kamera unit yang terdaftar pada grup teknis yang dipilih.';
            }
        }

        // Initialize state on load
        document.addEventListener('DOMContentLoaded', function() {
            const roleEl = document.getElementById('role');
            if (roleEl) handleRoleChange(roleEl.value);
        });
    </script>
@endsection