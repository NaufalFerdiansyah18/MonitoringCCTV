@extends('layouts.app')

@section('title', 'Dashboard — CCTV Monitoring')

@section('content')
    <div class="page-head">
        <h1>Dashboard</h1>
    </div>

    <p style="color:#6b7280; margin-bottom:18px; font-size:14px;">
        Unit yang dapat Anda akses:
    </p>

    @if ($units->isEmpty())
        <div class="card empty">Anda belum memiliki akses ke unit mana pun.</div>
    @else
        <div class="grid-units">
            @foreach ($units as $unit)
                <div class="unit-card">
                    <h3>{{ $unit->nama }}</h3>
                    <p>{{ $unit->kode }}</p>
                    <div>
                        <span class="badge badge-kategori">{{ ucfirst($unit->kategori) }}</span>
                    </div>
                    <p style="margin-top:12px; color:#9ca3af; font-size:12px;">Liveview: coming soon</p>
                </div>
            @endforeach
        </div>
    @endif
@endsection