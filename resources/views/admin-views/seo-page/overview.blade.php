@extends('layouts.admin.app')

@section('title', 'SEO Overview')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-header-title mb-0">SEO Overview</h1>
        <a href="{{ route('admin.seo-pages.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="tio-list"></i> Manage SEO Pages
        </a>
    </div>

    {{-- Landing coverage --}}
    <div class="row mb-2">
        <div class="col-md-3 mb-2">
            <div class="card text-center p-3">
                <div class="h3 mb-0">{{ $totals['total'] }}</div>
                <div class="text-muted small">Total landing combos</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card text-center p-3">
                <div class="h3 mb-0 text-success">{{ $totals['published'] }}</div>
                <div class="text-muted small">Published (live &amp; indexable)</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card text-center p-3">
                <div class="h3 mb-0 text-warning">{{ $totals['ungenerated'] }}</div>
                <div class="text-muted small">Ungenerated (no AI content yet)</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card text-center p-3">
                <div class="h3 mb-0 text-muted">{{ $totals['unpublished'] }}</div>
                <div class="text-muted small">Unpublished (no supply)</div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Level x status breakdown --}}
        <div class="col-lg-7 mb-3">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Coverage by level &amp; status</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-3">
                            <thead>
                                <tr class="text-muted small">
                                    <th>Level</th>
                                    <th class="text-success">Published</th>
                                    <th class="text-warning">Draft</th>
                                    <th class="text-muted">Unpublished</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Category</strong><div class="text-muted small">/{city}/services/{category}</div></td>
                                    <td>{{ $matrix['category']['published'] ?? 0 }}</td>
                                    <td>{{ $matrix['category']['draft'] ?? 0 }}</td>
                                    <td>{{ $matrix['category']['unpublished'] ?? 0 }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Item</strong><div class="text-muted small">/{city}/services/{category}/{item}</div></td>
                                    <td>{{ $matrix['item']['published'] ?? 0 }}</td>
                                    <td>{{ $matrix['item']['draft'] ?? 0 }}</td>
                                    <td>{{ $matrix['item']['unpublished'] ?? 0 }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex">
                        <div class="mr-4"><div class="h5 mb-0">{{ $categoriesCovered }}</div><div class="text-muted small">categories published</div></div>
                        <div><div class="h5 mb-0">{{ $citiesCovered }}</div><div class="text-muted small">cities with published pages</div></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Trust layer --}}
        <div class="col-lg-5 mb-3">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">MC Trust Layer</h5></div>
                <div class="card-body">
                    @if ($trust)
                        <div class="text-center mb-3">
                            <div class="h2 mb-0">{{ $trust['avg'] }}<span class="text-muted h5">/100</span></div>
                            <div class="text-muted small">average vendor trust score</div>
                        </div>
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td><span class="badge badge-soft-success">High</span> 71–100</td><td class="text-right">{{ $trust['high'] }}</td></tr>
                            <tr><td><span class="badge badge-soft-info">Mid</span> 41–70</td><td class="text-right">{{ $trust['mid'] }}</td></tr>
                            <tr><td><span class="badge badge-soft-warning">Low</span> 1–40</td><td class="text-right">{{ $trust['low'] }}</td></tr>
                            <tr><td><span class="badge badge-soft-secondary">Unscored</span> 0</td><td class="text-right">{{ $trust['zero'] }}</td></tr>
                        </table>
                    @else
                        <div class="text-muted small">
                            The <code>vendor_trust_score</code> column doesn't exist yet. Run
                            <code>php artisan vendor:sync-trust-score</code> on the server to create it and compute scores.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Top cities --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Published pages by city (top 15)</h5></div>
        <div class="card-body">
            @if ($topCities->isEmpty())
                <div class="text-muted small">No published landing pages yet. Generate + publish combos from the SEO Pages screen.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr class="text-muted small"><th>City / Zone</th><th class="text-right">Published pages</th></tr></thead>
                        <tbody>
                            @foreach ($topCities as $c)
                                <tr>
                                    <td>{{ $c->name }}</td>
                                    <td class="text-right">{{ $c->c }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
