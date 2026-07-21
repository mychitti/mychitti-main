@extends('layouts.admin.app')

@section('title', 'Store SEO Meta')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-header-title mb-0">Store SEO Meta <span class="badge badge-soft-dark">{{ $counts['total'] }}</span></h1>
        @if ($counts['missing'] > 0)
            <form method="POST" action="{{ route('admin.store-meta.generate-missing') }}"
                  onsubmit="return confirm('Queue AI meta generation for every store missing meta?');">
                @csrf
                @foreach (request()->only(['search', 'zone_id']) as $key => $val)
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endforeach
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="tio-magic-wand"></i> Generate all missing ({{ $counts['missing'] }})
                </button>
            </form>
        @endif
    </div>

    <div class="row mb-3">
        <div class="col-md-4 mb-2">
            <div class="card text-center p-3">
                <div class="h3 mb-0">{{ $counts['total'] }}</div>
                <div class="text-muted small">Total stores</div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-center p-3">
                <div class="h3 mb-0 text-success">{{ $counts['filled'] }}</div>
                <div class="text-muted small">Meta filled</div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-center p-3">
                <div class="h3 mb-0 text-warning">{{ $counts['missing'] }}</div>
                <div class="text-muted small">Missing meta</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row mb-3">
                <div class="col-md-3 mb-2">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="Search store name or address">
                </div>
                <div class="col-md-3 mb-2">
                    <select name="zone_id" class="form-control">
                        <option value="">All cities</option>
                        @foreach ($zones as $z)
                            <option value="{{ $z->id }}" {{ request('zone_id') == $z->id ? 'selected' : '' }}>
                                {{ $z->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control">
                        <option value="">All stores</option>
                        <option value="missing" {{ request('status') === 'missing' ? 'selected' : '' }}>Missing meta</option>
                        <option value="filled" {{ request('status') === 'filled' ? 'selected' : '' }}>Meta filled</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('admin.store-meta.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th>Store</th>
                            <th>Meta title</th>
                            <th>Meta description</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stores as $store)
                            @php
                                $hasMeta = $store->meta_title && $store->meta_description;
                            @endphp
                            <tr>
                                <td>
                                    <div class="font-weight-bold">{{ $store->name }}</div>
                                    <div class="text-muted small">#{{ $store->id }}</div>
                                </td>
                                <td style="max-width: 260px;">
                                    <span class="text-muted small">{{ $store->meta_title ?: '—' }}</span>
                                </td>
                                <td style="max-width: 340px;">
                                    <span class="text-muted small">{{ $store->meta_description ?: '—' }}</span>
                                </td>
                                <td class="text-center">
                                    @if ($hasMeta)
                                        <span class="badge badge-soft-success">Filled</span>
                                    @else
                                        <span class="badge badge-soft-warning">Missing</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('admin.store-meta.generate', $store->id) }}"
                                          onsubmit="return confirm('{{ $hasMeta ? 'This will overwrite the existing meta. Continue?' : 'Generate meta for this store?' }}');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <i class="tio-repeat"></i> {{ $hasMeta ? 'Regenerate' : 'Generate' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No stores match this filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {!! $stores->links() !!}
            </div>
        </div>
    </div>
</div>
@endsection
