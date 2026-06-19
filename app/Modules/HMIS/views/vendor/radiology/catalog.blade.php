@extends('layouts.vendor.app')
@section('title', 'Radiology — Scan Catalog')

@section('content')
<div class="content container-fluid"><div class="radx">
    @include('hmis::vendor.radiology._chrome')
    <div class="rad-body">
        @php
            $rxOwner = auth('vendor')->check();
            $canAdd  = $rxOwner || hasPermission('radiology_catalog', 'add');
            $canEdit = $rxOwner || hasPermission('radiology_catalog', 'edit');
            $canDel  = $rxOwner || hasPermission('radiology_catalog', 'delete');
            $cols = 'grid-template-columns:1.6fr 110px 1fr 110px 110px 90px 120px';
            $modPill = fn($m) => ['X-Ray'=>'pill-blue','CT Scan'=>'pill-purple','MRI'=>'pill-purple','Ultrasound'=>'pill-teal','ECG'=>'pill-amber'][$m] ?? 'pill-blue';
        @endphp
        <div class="card">
            <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltblue)">🗂</div> Scan Catalog &amp; Prices</h3>
                <div class="card-actions">
                    <form method="get" class="mb-0"><input class="fi" name="search" value="{{ request('search') }}" placeholder="Search scan / modality" style="width:200px"></form>
                    @if ($canAdd)<a href="{{ route('vendor.radiology.catalog.create') }}" class="btn btn-primary btn-sm">+ Add Scan</a>@endif
                </div>
            </div>
            <div class="tbl-hd" style="{{ $cols }}"><div>Scan</div><div>Modality</div><div>Body Part</div><div>TAT</div><div style="text-align:right">Price</div><div>Status</div><div></div></div>
            @forelse ($tests as $t)
                <div class="tbl-row" style="{{ $cols }}">
                    <div style="font-weight:700">{{ $t->name }}</div>
                    <div><span class="pill {{ $modPill($t->modality) }}">{{ $t->modality }}</span></div>
                    <div style="font-size:11px;color:var(--muted)">{{ $t->body_part ?: '—' }}</div>
                    <div style="font-size:11px;color:var(--muted)">{{ $t->tat_text ?: '—' }}</div>
                    <div class="num" style="text-align:right;font-weight:700">{{ \App\CentralLogics\Helpers::format_currency($t->price) }}</div>
                    <div>@if ($t->is_active)<span class="pill pill-green">Active</span>@else<span class="pill pill-red">Inactive</span>@endif</div>
                    <div style="display:flex;gap:4px;justify-content:flex-end">
                        @if ($canEdit)<a href="{{ route('vendor.radiology.catalog.edit', $t->id) }}" class="btn btn-outline btn-xs">Edit</a>@endif
                        @if ($canDel)<a href="{{ route('vendor.radiology.catalog.delete', $t->id) }}" onclick="return confirm('Remove this scan?')" class="btn btn-outline btn-xs">✕</a>@endif
                    </div>
                </div>
            @empty
                <div class="empty">No scans in the catalog yet. @if($canAdd)<a href="{{ route('vendor.radiology.catalog.create') }}">Add the first scan →</a>@endif</div>
            @endforelse
        </div>
    </div>
</div></div>
@endsection
