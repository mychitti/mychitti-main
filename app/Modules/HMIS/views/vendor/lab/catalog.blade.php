@extends('layouts.vendor.app')
@section('title', 'Laboratory — Test Catalog')

@section('content')
<div class="content container-fluid"><div class="labx">
    @include('hmis::vendor.lab._chrome')
    <div class="lab-body">
        @php $cols = 'display:grid;grid-template-columns:1.6fr 1fr 1.2fr 90px 90px 90px 80px;gap:8px'; @endphp
        <div class="lcard">
            <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltblue)">⚙</div> Test Catalog</h3>
                <div class="card-actions">
                    <form method="get" class="mb-0"><input class="fi" name="search" value="{{ request('search') }}" placeholder="Search test / code" style="width:200px"></form>
                    @if (hasPermission('lab_catalog', 'add'))<a href="{{ route('vendor.lab.catalog.create') }}" class="btn btn-primary btn-sm">+ Add Test</a>@endif
                </div>
            </div>
            <div class="tbl-hd" style="{{ $cols }}"><div>Test</div><div>Department</div><div>Sample</div><div>Params</div><div>TAT</div><div>Price</div><div></div></div>
            @forelse ($tests as $t)
                <div class="tbl-row" style="{{ $cols }}">
                    <div><div style="font-weight:700">{{ $t->name }}</div><div style="font-size:10px;color:var(--light)">{{ $t->code }}</div></div>
                    <div style="font-size:11px">{{ $t->department ?: '—' }}</div>
                    <div style="font-size:11px">{{ $t->sample_type ?: '—' }}</div>
                    <div><span class="pill pill-blue" style="font-size:9px">{{ $t->parameters_count }}</span></div>
                    <div style="font-size:11px;color:var(--muted)">{{ $t->tat_text ?: '—' }}</div>
                    <div class="num" style="font-size:12px">{{ \App\CentralLogics\Helpers::format_currency($t->price) }}</div>
                    <div style="display:flex;gap:4px">
                        @if (hasPermission('lab_catalog', 'edit'))<a href="{{ route('vendor.lab.catalog.edit', $t->id) }}" class="btn btn-outline btn-xs">Edit</a>@endif
                        @if (hasPermission('lab_catalog', 'delete'))<a href="{{ route('vendor.lab.catalog.delete', $t->id) }}" onclick="return confirm('Remove this test?')" class="btn btn-outline btn-xs">✕</a>@endif
                    </div>
                </div>
            @empty
                <div class="empty">No tests. <a href="{{ route('vendor.lab.catalog.create') }}">Add your first test →</a></div>
            @endforelse
        </div>
    </div>
</div></div>
@endsection
