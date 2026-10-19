@extends('layouts.app')

@section('title', 'Tambah Grup Teknis — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <div class="page-head-title-wrap">
            <h1>Tambah Grup Teknis</h1>
            <p class="page-subtitle">Buat kelompok akses teknis untuk mengelompokkan izin pantau unit kamera.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('admin.technical-groups.index') }}">
            Kembali ke Daftar
        </a>
    </div>

    <form class="card" method="POST" action="{{ route('admin.technical-groups.store') }}" style="max-width: 680px;">
        @csrf

        <div class="form-section-title">
            <span class="step-num">1</span> Nama Grup Teknis
        </div>

        <div class="form-group">
            <label for="nama">Nama Grup <span class="required-mark">*</span></label>
            <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required autofocus
                   placeholder="Contoh: Operasional PKS, Tim Biogas, Mandor Kebun">
            <div class="form-hint">Nama deskriptif untuk kelompok pengguna ini.</div>
            @error('nama') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="form-section-title" style="margin-top: 14px;">
            <span class="step-num">2</span> Hak Akses Kategori Unit
        </div>

        <div class="form-group">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <label style="margin-bottom: 0;">Centang Kategori yang Diizinkan <span class="required-mark">*</span></label>
                <div style="display: flex; gap: 6px;">
                    <button type="button" class="btn btn-secondary btn-xs" onclick="selectAllCategories(true)">Pilih Semua</button>
                    <button type="button" class="btn btn-secondary btn-xs" onclick="selectAllCategories(false)">Kosongkan</button>
                </div>
            </div>

            <div class="checkbox-cards-grid">
                @foreach ($kategoris as $kategori)
                    @php
                        $isChecked = in_array($kategori, old('kategoris', []));
                        $icons = [
                            'kebun' => '🌿',
                            'pks' => '🏭',
                            'biogas' => '⚡',
                        ];
                        $icon = $icons[strtolower($kategori)] ?? '📹';
                        $label = $kategori === 'pks' ? 'PKS (Pabrik)' : ($kategori === 'biogas' ? 'Pembangkit Biogas' : ucfirst($kategori));
                    @endphp
                    <label class="checkbox-card {{ $isChecked ? 'is-checked' : '' }}" style="cursor: pointer;">
                        <input type="checkbox" name="kategoris[]" value="{{ $kategori }}"
                               @checked($isChecked)
                               onchange="this.closest('.checkbox-card').classList.toggle('is-checked', this.checked)">
                        <span style="font-size: 18px;">{{ $icon }}</span>
                        <div>
                            <div style="font-weight: 600; font-size: 13px;">{{ $label }}</div>
                            <div style="font-size: 11px; color: #64748b;">Unit {{ strtoupper($kategori) }}</div>
                        </div>
                    </label>
                @endforeach
            </div>
            <div class="form-hint" style="margin-top: 8px;">User dalam grup ini hanya dapat melihat liveview kamera dari unit dengan kategori di atas.</div>
            @error('kategoris') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="info-banner" style="margin-top: 14px;">
            <strong>💡 Info:</strong> Pengguna dengan peran Teknis yang dimasukkan ke grup ini akan otomatis disesuaikan hak akses kameranya.
        </div>

        <div class="actions" style="margin-top: 24px;">
            <button class="btn btn-primary" type="submit">
                <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Simpan Grup Teknis
            </button>
            <a class="btn btn-secondary" href="{{ route('admin.technical-groups.index') }}">Batal</a>
        </div>
    </form>

    <script>
        function selectAllCategories(check) {
            document.querySelectorAll('.checkbox-card input[type="checkbox"]').forEach(function(cb) {
                cb.checked = check;
                cb.closest('.checkbox-card').classList.toggle('is-checked', check);
            });
        }
    </script>
@endsection