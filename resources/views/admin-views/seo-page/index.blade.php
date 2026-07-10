@extends('layouts.admin.app')

@section('title', 'SEO Pages')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-header-title mb-0">SEO Pages <span class="badge badge-soft-dark">{{ $combos->total() }}</span></h1>
    </div>

    <div class="row mb-3">
        <div class="col-md-3 mb-2">
            <div class="card text-center p-3">
                <div class="h3 mb-0">{{ $counts->sum() }}</div>
                <div class="text-muted small">Total combos</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card text-center p-3">
                <div class="h3 mb-0 text-success">{{ $counts['published'] ?? 0 }}</div>
                <div class="text-muted small">Published</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card text-center p-3">
                <div class="h3 mb-0 text-warning">{{ $counts['draft'] ?? 0 }}</div>
                <div class="text-muted small">Draft (not yet generated)</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card text-center p-3">
                <div class="h3 mb-0 text-muted">{{ $counts['unpublished'] ?? 0 }}</div>
                <div class="text-muted small">Unpublished (no supply)</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row mb-3">
                <div class="col-md-2 mb-2">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="Search">
                </div>
                <div class="col-md-2 mb-2">
                    <select name="category_id" class="form-control">
                        <option value="">All categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="zone_id" class="form-control">
                        <option value="">All cities</option>
                        @foreach ($zones as $z)
                            <option value="{{ $z->id }}" {{ request('zone_id') == $z->id ? 'selected' : '' }}>
                                {{ $z->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="level" class="form-control">
                        <option value="">Category + Item</option>
                        <option value="category" {{ request('level') === 'category' ? 'selected' : '' }}>Category pages</option>
                        <option value="item" {{ request('level') === 'item' ? 'selected' : '' }}>Item pages</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="status" class="form-control">
                        <option value="">All statuses</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="unpublished" {{ request('status') === 'unpublished' ? 'selected' : '' }}>Unpublished</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-primary btn-block" type="submit"><i class="tio-search"></i> Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Category / Item</th>
                            <th>City</th>
                            <th>Stores</th>
                            <th>Status</th>
                            <th>Meta title</th>
                            <th>Keywords</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($combos as $combo)
                            <tr>
                                <td>
                                    {{ $combo->category_name }}
                                    @if ($combo->item_id)
                                        <div class="small text-muted">&rarr; {{ $combo->item_name }}</div>
                                    @endif
                                </td>
                                <td>{{ $combo->zone_name }}</td>
                                <td>{{ $combo->store_count }}</td>
                                <td>
                                    @if ($combo->status === 'published')
                                        <span class="badge badge-soft-success">Published</span>
                                    @elseif ($combo->status === 'draft')
                                        <span class="badge badge-soft-warning">Draft</span>
                                    @else
                                        <span class="badge badge-soft-secondary">Unpublished</span>
                                    @endif
                                </td>
                                <td class="text-truncate" style="max-width:220px;">{{ $combo->meta_title }}</td>
                                <td>{{ $combo->keywords ? count($combo->keywords) : 0 }}</td>
                                <td>
                                    <div class="btn--container">
                                        <form action="{{ route('admin.seo-pages.generate', $combo->id) }}" method="POST"
                                              class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn action-btn btn--primary btn-outline-primary"
                                                    title="{{ $combo->keywords ? 'Regenerate' : 'Generate' }}">
                                                <i class="tio-repeat-vertical"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('admin.seo-pages.edit', $combo->id) }}"
                                           class="btn action-btn btn--warning btn-outline-warning" title="Edit / review">
                                            <i class="tio-edit"></i>
                                        </a>
                                        @if ($combo->status === 'published')
                                            <a href="{{ url($combo->slug) }}" target="_blank"
                                               class="btn action-btn btn--info btn-outline-info" title="View live page">
                                                <i class="tio-visible"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No SEO combos yet — they're created automatically once a store offers a service in a city.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $combos->links() }}
        </div>
    </div>
</div>
@endsection
