@extends('front-views.layout')
@section('title', 'Our Doctors — ' . $store->name)

@push('css_or_js')
<style>
    .docs-wrap { max-width: 1100px; margin: 0 auto; padding: 100px 16px 70px; }

    .docs-store-header {
        display:flex; align-items:center; gap:14px;
        margin-bottom:24px; padding-bottom:18px; border-bottom:1px solid #eef0f3;
    }
    .docs-store-logo { width:56px; height:56px; border-radius:12px; object-fit:cover; border:1px solid #eee; flex-shrink:0; }
    .docs-store-name { font-size:22px; font-weight:700; margin:0; color:#111827; line-height:1.2; }
    .docs-store-sub  { font-size:13px; color:#6b7280; margin:2px 0 0; }

    .docs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 18px;
        align-items: stretch;
    }
    .doc-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 18px;
        display: flex;
        gap: 16px;
        align-items: flex-start;
        transition: box-shadow .15s, border-color .15s, transform .15s;
        height: 100%;
    }
    .doc-card:hover { border-color:#bfdbfe; box-shadow:0 8px 24px rgba(37,99,235,.10); transform:translateY(-2px); }

    .doc-photo, .doc-photo-fallback {
        width: 88px; height: 88px; border-radius: 14px; flex-shrink: 0;
    }
    .doc-photo { object-fit: cover; background:#eef2ff; border:1px solid #eef2ff; }
    .doc-photo-fallback {
        background:#dbeafe; color:#2563eb; font-size:34px; font-weight:700;
        display:flex; align-items:center; justify-content:center;
    }

    .doc-body { min-width: 0; flex: 1; }
    .doc-name { font-size:16px; font-weight:700; color:#111827; margin:0 0 5px; }
    .doc-spec {
        display:inline-block; font-size:12px; font-weight:600; color:#2563eb;
        background:#eff6ff; padding:2px 10px; border-radius:999px; margin:0 0 10px;
    }
    .doc-meta { font-size:12.5px; color:#6b7280; margin:0 0 4px; line-height:1.45; word-break:break-word; }
    .doc-meta b { color:#374151; font-weight:600; }
    .doc-fee {
        font-size:13px; font-weight:700; color:#059669; margin:10px 0 0;
        display:inline-block; background:#ecfdf5; padding:3px 10px; border-radius:8px;
    }
    .doc-fee.free { color:#7c3aed; background:#f5f3ff; }

    .docs-empty { grid-column:1/-1; text-align:center; color:#9ca3af; padding:70px 0; font-size:15px; }

    @media (max-width: 600px) {
        .docs-wrap { padding-top: 84px; }
        .docs-grid { grid-template-columns: 1fr; }
        .doc-photo, .doc-photo-fallback { width:72px; height:72px; }
    }
</style>
@endpush

@section('content')
<div class="docs-wrap">

    {{-- Store Header --}}
    <div class="docs-store-header">
        @if($store->logo)
            <img src="{{ asset('storage/app/public/store/' . $store->logo) }}" class="docs-store-logo" alt="{{ $store->name }}">
        @endif
        <div>
            <p class="docs-store-name">{{ $store->name }}</p>
            <p class="docs-store-sub">Our Doctors</p>
        </div>
    </div>

    <div class="docs-grid">
        @forelse($doctors as $d)
            @php
                $name    = 'Dr. ' . trim(($d->employee?->f_name ?? '') . ' ' . ($d->employee?->l_name ?? ''));
                $initial = strtoupper(substr($d->employee?->f_name ?? 'D', 0, 1));

                $days = $d->available_days;
                if (is_string($days)) {
                    $decoded = json_decode($days, true);
                    $days = is_array($decoded) ? implode(', ', $decoded) : $days;
                } elseif (is_array($days)) {
                    $days = implode(', ', $days);
                }
                $days = $days ? preg_replace('/\s*,\s*/', ', ', trim($days)) : null;
            @endphp
            <div class="doc-card">
                @if($d->employee?->image)
                    <img class="doc-photo" src="{{ asset('storage/app/public/vendor/' . $d->employee->image) }}"
                        alt="{{ $name }}"
                        onerror="this.onerror=null; this.outerHTML='<div class=&quot;doc-photo-fallback&quot;>{{ $initial }}</div>';">
                @else
                    <div class="doc-photo-fallback">{{ $initial }}</div>
                @endif

                <div class="doc-body">
                    <p class="doc-name">{{ $name }}</p>
                    @if($d->specialization)<span class="doc-spec">{{ $d->specialization }}</span>@endif
                    @if($d->qualification)<p class="doc-meta"><b>Qualification:</b> {{ $d->qualification }}</p>@endif
                    @if($d->department)<p class="doc-meta"><b>Department:</b> {{ $d->department }}</p>@endif
                    @if(!empty($days))<p class="doc-meta"><b>Available:</b> {{ $days }}</p>@endif
                    @if($d->consultation_fee > 0)
                        <span class="doc-fee">₹{{ number_format($d->consultation_fee, 0) }} consultation</span>
                    @else
                        <span class="doc-fee free">Free consultation</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="docs-empty">No doctors available at this store.</div>
        @endforelse
    </div>
</div>
@endsection
