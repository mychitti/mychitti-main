@extends('layouts.admin.app')
@section('title', 'Pool Suggestions')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .cs-table { min-width: 1000px; }
        .cs-verdict { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; padding:2px 8px; border-radius:100px; }
        .cs-new       { background:#e8f5e9; color:#2e7d32; }
        .cs-duplicate { background:#fff8e1; color:#b45309; }
        .cs-unsure    { background:#eef2f7; color:#5f6c7b; }
        .cs-invalid   { background:#ffebee; color:#c62828; }
        .cs-reason { font-size:11px; color:#8c98a4; }
        .cs-count { font-size:11px; font-weight:700; color:#377dff; }
        .cs-inline { display:inline-flex; align-items:center; gap:4px; }
        .cs-inline .form-control { height:30px; font-size:12px; }
    </style>
@endpush

@section('content')
<div class="content container-fluid">

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px;">
        <div>
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-inbox"></i></span>
                Pool Suggestions
            </h1>
            <span class="text-muted" style="font-size:12px;">
                What stores typed that the pool does not have. Sorted by how many stores asked, so the
                gaps worth filling come first. Nothing here is in the catalog until you approve it.
            </span>
        </div>
        <div class="d-flex" style="gap:8px;">
            <form method="post" action="{{ route('admin.catalog.suggestions.verify') }}" class="mb-0">
                @csrf
                <input type="hidden" name="domain" value="{{ $domain }}">
                <button class="btn btn-sm btn-outline-secondary"><i class="tio-refresh"></i> Re-check pending</button>
            </form>
            <a href="{{ route('admin.catalog.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="tio-book-opened"></i> The pool
            </a>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        @php
            $tabs = [
                'open'      => 'Needs review',
                'ready'     => 'AI says new',
                'duplicate' => 'AI says duplicate',
                'unsure'    => 'Unsure',
                'approved'  => 'Approved',
                'rejected'  => 'Rejected',
                'all'       => 'All',
            ];
        @endphp
        @foreach($tabs as $key => $label)
            <li class="nav-item">
                <a class="nav-link {{ $status === $key ? 'active' : '' }}"
                   href="{{ route('admin.catalog.suggestions', ['status' => $key, 'domain' => $domain]) }}">
                    {{ $label }}
                    @if($key !== 'open' && $key !== 'all' && ($tallies[$key] ?? 0))
                        <span class="badge badge-soft-secondary ml-1">{{ $tallies[$key] }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-align-middle mb-0 cs-table" style="font-size:13px">
                <thead class="thead-light">
                    <tr>
                        <th>What the store typed</th>
                        <th style="width:120px;">Asked by</th>
                        <th style="width:150px;">AI verdict</th>
                        <th style="width:230px;">Matched against</th>
                        <th style="width:280px;"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($suggestions as $s)
                    <tr>
                        <td>
                            <div class="font-weight-bold">{{ $s->label }}</div>
                            <div class="cs-reason">{{ $s->meta_label }}</div>
                        </td>
                        <td>
                            <span class="cs-count">{{ $s->request_count }}</span>
                            <span class="text-muted" style="font-size:11px;">{{ $s->request_count == 1 ? 'store' : 'stores' }}</span>
                            @if($s->store)<div class="cs-reason">{{ $s->store->name }}</div>@endif
                        </td>
                        <td>
                            @if($s->ai_verdict)
                                <span class="cs-verdict cs-{{ $s->ai_verdict }}">{{ $s->ai_verdict }}</span>
                                @if($s->ai_confidence)<span class="cs-reason">{{ round($s->ai_confidence * 100) }}%</span>@endif
                                <div class="cs-reason">{{ \Illuminate\Support\Str::limit($s->ai_reason, 70) }}</div>
                            @else
                                <span class="cs-verdict cs-unsure">not checked</span>
                            @endif
                        </td>
                        <td>
                            @if($s->match)
                                <div>{{ $s->match->label }}</div>
                                <div class="cs-reason">id {{ $s->match->id }} · {{ $s->match->brand ?: 'Generic' }}</div>
                            @elseif($s->catalogItem)
                                <div class="text-success">{{ $s->catalogItem->label }}</div>
                                <div class="cs-reason">id {{ $s->catalogItem->id }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if(in_array($s->status, \App\Models\CatalogSuggestion::OPEN_STATUSES))
                                <form method="post" action="{{ route('admin.catalog.suggestions.approve', $s->id) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="name" value="{{ $s->raw_name }}">
                                    <input type="hidden" name="brand" value="{{ $s->raw_brand }}">
                                    <input type="hidden" name="strength" value="{{ $s->raw_strength }}">
                                    <input type="hidden" name="form" value="{{ $s->raw_form }}">
                                    <button class="btn btn-sm btn-primary">Add to pool</button>
                                </form>

                                <form method="post" action="{{ route('admin.catalog.suggestions.merge', $s->id) }}" class="d-inline cs-inline">
                                    @csrf
                                    <input type="number" name="catalog_item_id" class="form-control form-control-sm"
                                           style="width:92px;" placeholder="id"
                                           value="{{ $s->match_catalog_item_id }}" required>
                                    <button class="btn btn-sm btn-outline-secondary">Merge</button>
                                </form>

                                <a href="{{ route('admin.catalog.suggestions.reject', $s->id) }}"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Reject this entry?')">✕</a>
                            @else
                                <span class="badge badge-soft-secondary">{{ ucfirst($s->status) }}</span>
                                @if(!$s->reviewed_by && $s->reviewed_at)
                                    <span class="badge badge-soft-info ml-1"
                                          title="Decided by the AI check at {{ number_format(($s->ai_confidence ?? 0) * 100) }}% confidence — no admin reviewed it">
                                        <i class="tio-magic-wand"></i> Auto
                                    </span>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">
                        Nothing here. Entries appear as stores add medicines the pool does not have.
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{!! $suggestions->links() !!}</div>
</div>
@endsection
