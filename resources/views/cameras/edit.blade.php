@extends('layouts.app')

@section('title', 'Edit Kamera — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <div class="page-head-title-wrap">
            <h1>Edit Kamera CH {{ $camera->channel }} — {{ $dvr->nama }}</h1>
            <p class="page-subtitle">Unit: <strong>{{ $dvr->unit?->kode }} — {{ $dvr->unit?->nama }}</strong> | IP: <code>{{ $dvr->ip_local }}</code></p>
        </div>
        <a class="btn btn-secondary" href="{{ route('admin.dvrs.cameras.index', $dvr) }}">← Kembali ke Daftar Kamera</a>
    </div>

    <form class="card" method="POST" action="{{ route('admin.dvrs.cameras.update', [$dvr, $camera]) }}" style="max-width: 680px;">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="channel">Channel Kamera <span class="required-mark">*</span></label>
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
                <input type="number" id="channel" name="channel" value="{{ old('channel', $camera->channel) }}" required min="1" max="16" style="max-width:140px;" placeholder="1 - 16">
                <span style="font-size:12.5px; color:#64748b;">Pilih nomor channel dari tombol di bawah:</span>
            </div>

            <div class="channel-grid">
                @for ($ch = 1; $ch <= 16; $ch++)
                    @php $isUsed = in_array($ch, $usedChannels ?? []); @endphp
                    <button type="button"
                            class="channel-btn {{ $isUsed ? 'is-occupied' : '' }} {{ $camera->channel == $ch ? 'is-active' : '' }}"
                            @if ($isUsed) disabled title="Channel {{ $ch }} sudah terpakai" @else onclick="selectChannel({{ $ch }}, this)" title="Pilih Channel {{ $ch }}" @endif>
                        {{ $ch }}
                    </button>
                @endfor
            </div>
            <p class="form-hint" style="margin-top:6px;">Channel 1 sampai 16 pada DVR. Angka yang dicoret menandakan channel sudah dipakai kamera lain.</p>
            @error('channel') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="nama_lokasi">Nama Lokasi Kamera <span class="required-mark">*</span></label>
            <input type="text" id="nama_lokasi" name="nama_lokasi" value="{{ old('nama_lokasi', $camera->nama_lokasi) }}" required
                   placeholder="Contoh: Gerbang Masuk, Timbangan TBS, Loading Ramp">
            <p class="form-hint">Keterangan lokasi titik pasang kamera.</p>
            @error('nama_lokasi') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="kategori">Kategori Kamera <small style="font-weight:400; color:#6b7280;">(Opsional)</small></label>
            <input type="text" id="kategori" name="kategori" value="{{ old('kategori', $camera->kategori) }}"
                   placeholder="Ketik atau pilih tag di bawah:">
            <div class="quick-tags">
                <span class="quick-tag" onclick="setCategory('Timbangan')">+ Timbangan</span>
                <span class="quick-tag" onclick="setCategory('Gerbang')">+ Gerbang</span>
                <span class="quick-tag" onclick="setCategory('Loading')">+ Loading</span>
                <span class="quick-tag" onclick="setCategory('Produksi')">+ Produksi</span>
                <span class="quick-tag" onclick="setCategory('Gudang')">+ Gudang</span>
                <span class="quick-tag" onclick="setCategory('Keamanan')">+ Keamanan</span>
                <span class="quick-tag" onclick="setCategory('Power')">+ Power</span>
                <span class="quick-tag" onclick="setCategory('Reaktor')">+ Reaktor</span>
            </div>
            <p class="form-hint">Kategori kamera membantu penyaringan cepat di monitor liveview.</p>
            @error('kategori') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="actions" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
            <button class="btn btn-primary" type="submit">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                Simpan Perubahan
            </button>
            <a class="btn btn-secondary" href="{{ route('admin.dvrs.cameras.index', $dvr) }}">Batal</a>
        </div>
    </form>

    <script>
        function selectChannel(ch, btn) {
            document.getElementById('channel').value = ch;
            document.querySelectorAll('.channel-btn').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
        }

        function setCategory(val) {
            document.getElementById('kategori').value = val;
        }
    </script>
@endsection