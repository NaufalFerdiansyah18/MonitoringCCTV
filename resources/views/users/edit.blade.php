@extends('layouts.app')

@section('title', 'Edit User — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <div class="page-head-title-wrap">
            <h1>Edit Pengguna: {{ $user->name }}</h1>
            <p class="page-subtitle">Perbarui profil, kata sandi, atau hak akses akun pengguna ini.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">
            Kembali ke Daftar
        </a>
    </div>

    @if ($user->is(auth()->user()))
        <div class="info-banner" style="max-width: 720px; margin-bottom: 18px; border-left: 4px solid #f59e0b; background: #fffbeb;">
            <strong>⚠️ Perhatian Akun Sendiri:</strong> Anda sedang mengubah akun yang sedang aktif digunakan. Pengubahan peran (role) dan grup teknis dinonaktifkan demi keamanan sesi Anda.
        </div>
    @endif

    <form class="card" method="POST" action="{{ route('admin.users.update', $user) }}" style="max-width: 720px;">
        @csrf
        @method('PUT')

        <div class="form-section-title">
            <span class="step-num">1</span> Informasi Akun Pengguna
        </div>

        <div class="form-group">
            <label for="name">Nama Lengkap <span class="required-mark">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
            <div class="form-hint">Nama personel atau nama posisi yang bertugas memantau kamera.</div>
            @error('name') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Alamat Email <span class="required-mark">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                <div class="form-hint">Digunakan saat proses login.</div>
                @error('email') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="password">Password Baru <span style="font-weight:400; color:#64748b;">(opsional)</span></label>
                <div class="password-input-wrap">
                    <input type="password" id="password" name="password" placeholder="Kosongkan jika tetap">
                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', this)" title="Lihat/Sembunyikan Password">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <div class="form-hint">Hanya isi bidang ini apabila ingin mereset password akun.</div>
                @error('password') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-section-title" style="margin-top: 10px;">
            <span class="step-num">2</span> Peran & Hak Akses
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="role">Peran Sistem (Role) <span class="required-mark">*</span></label>
                <select id="role" name="role" @disabled($user->is(auth()->user())) required onchange="handleRoleChange(this.value)">
                    <option value="teknis" @selected(old('role', $user->role) === 'teknis')>Teknis (Petugas Unit / Kios)</option>
                    <option value="superadmin" @selected(old('role', $user->role) === 'superadmin')>Superadmin (Akses Penuh)</option>
                </select>
                <div class="form-hint">Tingkat wewenang user dalam sistem.</div>
                @error('role') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group" id="group-select-wrap">
                <label for="technical_group_id">Grup Teknis Unit</label>
                <select id="technical_group_id" name="technical_group_id" @disabled($user->is(auth()->user()))>
                    <option value="">— Pilih grup unit —</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}" @selected(old('technical_group_id', $user->technical_group_id) == $group->id)>
                            {{ $group->nama }}
                        </option>
                    @endforeach
                </select>
                <div class="form-hint" id="group-hint">Menentukan unit kamera yang berhak diakses akun ini.</div>
                @error('technical_group_id') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div id="role-info-banner" class="info-banner" style="margin-top: 10px;">
            <strong>ℹ️ Catatan Peran:</strong> <span id="role-info-text">Akun Teknis dapat memantau liveview kamera unit yang terdaftar pada grup teknis yang dipilih.</span>
        </div>

        <div class="actions" style="margin-top: 24px;">
            <button class="btn btn-primary" type="submit">
                <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                Perbarui Pengguna
            </button>
            <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">Batal</a>
        </div>
    </form>

    <script>
        function handleRoleChange(role) {
            const groupSelect = document.getElementById('technical_group_id');
            const groupHint = document.getElementById('group-hint');
            const infoText = document.getElementById('role-info-text');

            if (!groupSelect) return;

            const isSelf = {{ $user->is(auth()->user()) ? 'true' : 'false' }};
            if (isSelf) return;

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

        document.addEventListener('DOMContentLoaded', function() {
            const roleEl = document.getElementById('role');
            if (roleEl) handleRoleChange(roleEl.value);
        });
    </script>
@endsection