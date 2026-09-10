@extends('layouts.admin.app')

@section('title', 'Search Console')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-google"></i></span>
                Search Console
            </h1>
            <p class="text-muted small mb-0">
                Real search performance for mychitti.net — clicks, impressions, CTR and average position, straight from Google.
                @if ($lastSynced)
                    Last synced {{ \Carbon\Carbon::parse($lastSynced)->diffForHumans() }}.
                @endif
            </p>
        </div>
        <form method="POST" action="{{ route('admin.search-console.sync') }}">
            @csrf
            <button type="submit" class="btn btn--primary btn-sm">
                <i class="tio-repeat"></i> Sync now
            </button>
        </form>
    </div>

    @if (!$hasData || $daily->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <img class="w--120px mb-3" src="{{ asset('/public/assets/admin/img/empty-box.png') }}" alt="">
                <h5 class="mb-1">No data synced yet</h5>
                <p class="text-muted mb-3">Click "Sync now" to pull the last 30 days from Search Console (or wait for tonight's scheduled sync).</p>
            </div>
        </div>
    @else
        {{-- Totals --}}
        <div class="row mb-3">
            <div class="col-md-3 mb-2">
                <div class="card text-center p-3">
                    <div class="h3 mb-0">{{ number_format($totals['clicks']) }}</div>
                    <div class="text-muted small">Total clicks</div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card text-center p-3">
                    <div class="h3 mb-0">{{ number_format($totals['impressions']) }}</div>
                    <div class="text-muted small">Total impressions</div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card text-center p-3">
                    <div class="h3 mb-0">{{ $totals['avg_ctr'] }}%</div>
                    <div class="text-muted small">Average CTR</div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card text-center p-3">
                    <div class="h3 mb-0">{{ $totals['avg_position'] }}</div>
                    <div class="text-muted small">Average position</div>
                </div>
            </div>
        </div>

        {{-- Trend --}}
        <div class="card mb-3">
            <div class="card-header border-0"><h5 class="mb-0">Clicks &amp; impressions over time</h5></div>
            <div class="card-body">
                <div id="scTrendChart"></div>
            </div>
        </div>

        <div class="row">
            {{-- Top pages --}}
            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header border-0"><h5 class="mb-0">Top pages by clicks</h5></div>
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>Page</th>
                                    <th class="text-right">Clicks</th>
                                    <th class="text-right">Impr.</th>
                                    <th class="text-right">CTR</th>
                                    <th class="text-right">Pos.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topPages as $row)
                                    <tr>
                                        <td style="max-width: 320px;">
                                            <a href="{{ $row->page }}" target="_blank" rel="noopener" class="d-block text-truncate small">
                                                {{ str_replace(['https://mychitti.net/', 'https://mychitti.net'], '/', $row->page) ?: '/' }}
                                            </a>
                                        </td>
                                        <td class="text-right">{{ number_format($row->clicks) }}</td>
                                        <td class="text-right">{{ number_format($row->impressions) }}</td>
                                        <td class="text-right">{{ round($row->ctr * 100, 1) }}%</td>
                                        <td class="text-right">{{ round($row->position, 1) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">No page data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Top queries --}}
            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header border-0"><h5 class="mb-0">Top search queries</h5></div>
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>Query</th>
                                    <th class="text-right">Clicks</th>
                                    <th class="text-right">Impr.</th>
                                    <th class="text-right">CTR</th>
                                    <th class="text-right">Pos.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topQueries as $row)
                                    <tr>
                                        <td style="max-width: 260px;"><span class="d-block text-truncate small">{{ $row->search_query }}</span></td>
                                        <td class="text-right">{{ number_format($row->clicks) }}</td>
                                        <td class="text-right">{{ number_format($row->impressions) }}</td>
                                        <td class="text-right">{{ round($row->ctr * 100, 1) }}%</td>
                                        <td class="text-right">{{ round($row->position, 1) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">No query data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Internal linking — a real crawl of our own SEO page graph, not Google data. --}}
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4">
        <div>
            <h4 class="mb-0">Internal linking</h4>
            <p class="text-muted small mb-0">
                Crawls the ~1,900 published category/SEO landing pages and counts links between them — the same way Google discovers pages, not a guess from the link-building code.
                @if ($linkAuditCheckedAt)
                    Last audited {{ \Carbon\Carbon::parse($linkAuditCheckedAt)->diffForHumans() }}.
                @endif
            </p>
        </div>
        <span class="badge badge-soft-secondary" title="Runs automatically every Sunday at 4am — for an on-demand run, use the CLI">
            <i class="tio-time"></i> Runs Sunday 4am, or: <code>php artisan internal-links:audit</code>
        </span>
    </div>

    @if (!$hasLinkAudit || $linkAuditStats['total'] === 0)
        <div class="card">
            <div class="card-body text-center py-5">
                <img class="w--120px mb-3" src="{{ asset('/public/assets/admin/img/empty-box.png') }}" alt="">
                <h5 class="mb-1">No audit run yet</h5>
                <p class="text-muted mb-0">Click "Run audit" — it's queued, not instant, since it crawls ~1,900 pages. Check back in a few minutes, or wait for Sunday's scheduled run.</p>
            </div>
        </div>
    @else
        <div class="row mb-3">
            <div class="col-md-3 mb-2">
                <div class="card text-center p-3">
                    <div class="h3 mb-0">{{ number_format($linkAuditStats['total']) }}</div>
                    <div class="text-muted small">Pages crawled</div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card text-center p-3">
                    <div class="h3 mb-0 {{ $linkAuditStats['orphans'] > 0 ? 'text-warning' : 'text-success' }}">{{ number_format($linkAuditStats['orphans']) }}</div>
                    <div class="text-muted small">Orphan pages (0 inbound links)</div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card text-center p-3">
                    <div class="h3 mb-0 {{ $linkAuditStats['broken'] > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($linkAuditStats['broken']) }}</div>
                    <div class="text-muted small">Broken (non-200 on crawl)</div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card text-center p-3">
                    <div class="h3 mb-0">{{ $linkAuditStats['avg_outbound'] }}</div>
                    <div class="text-muted small">Avg. outbound links / page</div>
                </div>
            </div>
        </div>

        @if ($orphanPages->isNotEmpty())
            <div class="card">
                <div class="card-header border-0">
                    <h5 class="mb-0">Orphan pages</h5>
                    <p class="text-muted small mb-0">Published, load fine (HTTP 200), but no other page in the graph links to them — findable only via the sitemap, not by browsing.</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th>URL</th>
                                <th class="text-right">Outbound links</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orphanPages as $row)
                                <tr>
                                    <td><a href="{{ $row->url }}" target="_blank" rel="noopener" class="small">{{ str_replace('https://mychitti.net', '', $row->url) }}</a></td>
                                    <td class="text-right">{{ $row->outbound_links }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($linkAuditStats['orphans'] > $orphanPages->count())
                    <div class="card-footer text-muted small">Showing the first {{ $orphanPages->count() }} of {{ $linkAuditStats['orphans'] }} orphan pages.</div>
                @endif
            </div>
        @endif
    @endif
</div>
@endsection

@if ($hasData && $daily->isNotEmpty())
@push('script_2')
<script src="{{ asset('/public/assets/admin/js/apex-charts/apexcharts.js') }}"></script>
<script>
"use strict";
(function () {
    const labels = @json($daily->pluck('stat_date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M')));
    const clicks = @json($daily->pluck('clicks'));
    const impressions = @json($daily->pluck('impressions'));

    const options = {
        chart: { type: 'area', height: 300, toolbar: { show: false }, zoom: { enabled: false } },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 } },
        series: [
            { name: 'Clicks', data: clicks },
            { name: 'Impressions', data: impressions },
        ],
        colors: ['#377dff', '#00c9a7'],
        xaxis: { categories: labels, labels: { rotate: -45, style: { fontSize: '11px' } } },
        yaxis: [
            { title: { text: 'Clicks' } },
            { opposite: true, title: { text: 'Impressions' } },
        ],
        tooltip: { shared: true, intersect: false, y: { formatter: v => Number(v).toLocaleString() } },
        legend: { position: 'top' },
        grid: { borderColor: '#e7eaf3', strokeDashArray: 3 },
    };

    new ApexCharts(document.querySelector('#scTrendChart'), options).render();
})();
</script>
@endpush
@endif
