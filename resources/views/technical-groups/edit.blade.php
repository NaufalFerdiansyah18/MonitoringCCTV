@extends('layouts.app')

@section('title', 'Edit Grup Teknis — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <div class="page-head-title-wrap">
            <h1>Edit Grup Teknis: {{ $group->nama }}</h1>
            <p class="page-subtitle">Perbarui nama kelompok dan cakupan izin kategori unit kamera.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('admin.technical-groups.index') }}">
            Kembali ke Daftar
        </a>
    </div>

    <form class="card" method="POST" action="{{ route('admin.technical-groups.update', $group) }}" style="max-width: 680px;">
        @csrf
        @method('PUT')

        <div class="form-section-title">
            <span class="step-num">1</span> Nama Grup Teknis
        </div>

        <div class="form-group">
            <label for="nama">Nama Grup <span class="required-mark">*</span></label>
            <input type="text" id="nama" name="nama" value="{{ old('nama', $group->nama) }}" required autofocus>
            <div class="form-hint">Nama kelompok teknis.</div>
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

            @php
                $existing = $group->unitCategoryRows->pluck('kategori')->all();
                $icons = [
                    'kebun' => '🌿',
                    'pks' => '🏭',
                    'biogas' => '⚡',
                ];
            @endphp

            <div class="checkbox-cards-grid">
                @foreach ($kategoris as $kategori)
                    @php
                        $isChecked = in_array($kategori, old('kategoris', $existing));
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
            <div class="form-hint" style="margin-top: 8px;">Perubahan kategori di sini akan langsung berdampak ke seluruh user di dalam grup ini.</div>
            @error('kategoris') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="actions" style="margin-top: 24px;">
            <button class="btn btn-primary" type="submit">
                <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                Perbarui Grup Teknis
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