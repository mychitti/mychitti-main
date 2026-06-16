@extends('layouts.vendor.app')
@section('title', 'Laboratory — Reagents & Consumables')

@push('css_or_js')<meta name="csrf-token" content="{{ csrf_token() }}">@endpush

@section('content')
<div class="content container-fluid"><div class="labx">
    @include('hmis::vendor.lab._chrome')
    <div class="lab-body">
        @php $cols = 'display:grid;grid-template-columns:2fr 1.2fr 1fr 90px 90px 90px 70px;gap:8px'; @endphp
        <div class="lcard">
            <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltpurple)">🧪</div> Reagents &amp; Consumables</h3>
                @if (hasPermission('lab_reagent', 'add'))<button class="btn btn-primary btn-sm" onclick="document.getElementById('addReagent').style.display='block'">+ Add Reagent</button>@endif
            </div>
            @if (hasPermission('lab_reagent', 'add'))
                <form method="post" action="{{ route('vendor.lab.reagents.save') }}" id="addReagent" style="display:none;padding:14px;background:#FAFAFA;border-bottom:1px solid var(--border)">
                    @csrf
                    <div class="frow4">
                        <div class="fg"><label class="fl">Reagent / Kit *</label><input class="fi" name="name" required></div>
                        <div class="fg"><label class="fl">Machine</label><input class="fi" name="machine"></div>
                        <div class="fg"><label class="fl">For Test</label><input class="fi" name="for_test"></div>
                        <div class="fg"><label class="fl">Expiry</label><input class="fi" type="date" name="expiry_date"></div>
                    </div>
                    <div class="frow4">
                        <div class="fg"><label class="fl">Stock</label><input class="fi" type="number" step="0.01" name="stock" value="0"></div>
                        <div class="fg"><label class="fl">Min Level</label><input class="fi" type="number" step="0.01" name="min_level" value="0"></div>
                        <div class="fg"><label class="fl">Unit Label</label><input class="fi" name="unit_label" value="tests"></div>
                        <div class="fg" style="justify-content:flex-end"><button class="btn btn-primary">Save Reagent</button></div>
                    </div>
                </form>
            @endif
            <div class="tbl-hd" style="{{ $cols }}"><div>Reagent / Kit</div><div>For Test</div><div>Expiry</div><div>Stock</div><div>Min</div><div>Status</div><div></div></div>
            @forelse ($reagents as $r)
                @php
                    $lvl = $r->statusLevel();
                    $bg = $lvl === 'critical' || $lvl === 'out' ? '#FFF5F5' : ($lvl === 'low' ? '#FFFBEB' : 'transparent');
                    $pillCls = $lvl === 'ok' ? 'pill-green' : ($lvl === 'low' ? 'pill-amber' : 'pill-red');
                    $pillTxt = ['ok' => 'OK', 'low' => 'Low', 'critical' => 'Critical', 'out' => 'Out'][$lvl];
                    $stockColor = $lvl === 'ok' ? 'var(--greenA)' : ($lvl === 'low' ? 'var(--amber)' : 'var(--redA)');
                @endphp
                <div class="tbl-row" style="{{ $cols }};background:{{ $bg }}">
                    <div><div style="font-weight:700">{{ $r->name }}</div><div style="font-size:10px;color:var(--light)">{{ $r->machine }}</div></div>
                    <div style="font-size:11px">{{ $r->for_test }}</div>
                    <div style="font-size:11px;color:var(--muted)">{{ $r->expiry_date?->format('M Y') ?? '—' }}</div>
                    <div class="num" style="color:{{ $stockColor }};font-weight:700">{{ (int) $r->stock }} {{ $r->unit_label }}</div>
                    <div style="font-size:11px;color:var(--muted)">{{ (int) $r->min_level }}</div>
                    <div><span class="pill {{ $pillCls }}" style="font-size:9px">{{ $pillTxt }}</span></div>
                    <div>@if (hasPermission('lab_reagent', 'delete'))<a href="{{ route('vendor.lab.reagents.delete', $r->id) }}" onclick="return confirm('Remove?')" class="btn btn-outline btn-xs">✕</a>@endif</div>
                </div>
            @empty
                <div class="empty">No reagents tracked yet.</div>
            @endforelse
        </div>
    </div>
</div></div>
@endsection
