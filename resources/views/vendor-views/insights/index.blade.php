@extends('layouts.vendor.app')

@section('title', 'Business Insights')

@php
    $trust    = (int) ($store->vendor_trust_score ?? 0);
    $rating   = $store && $store->average_rating !== null ? round((float) $store->average_rating, 1) : null;
    $maxDaily = max(1, max($daily));
    $sentColors = ['positive' => 'success', 'neutral' => 'secondary', 'negative' => 'danger'];
@endphp
  
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">Business Insights</h1>
        <p class="mb-0">How your listing is trending and how customers feel about you — last 30 days.</p>
    </div>

    {{-- KPI row --}}
    <div class="row">
        @php
            $kpis = [
                ['Leads (30d)', $leadTotal, 'tio-chart-bar-4', 'primary'],
                ['Trust score', $trust . '/100', 'tio-verified', 'success'],
                ['Avg rating', $rating !== null ? $rating . ' ★' : '—', 'tio-star', 'warning'],
                ['Reviews', (int) ($store->rating_count ?? 0), 'tio-comment', 'info'],
                ['Active offers', $activeOffers, 'tio-gift', 'danger'],
            ];
        @endphp
        @foreach ($kpis as [$label, $val, $icon, $color])
            <div class="col-6 col-lg mb-3">
                <div class="card h-100"><div class="card-body text-center py-3">
                    <i class="{{ $icon }} text-{{ $color }}"></i>
                    <span class="d-block text-muted small mt-1">{{ $label }}</span>
                    <span class="h4 mb-0">{{ $val }}</span>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="row">
        {{-- Daily leads bar chart --}}
        <div class="col-lg-7 mb-3">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Customer contacts — last 14 days</h5></div>
                <div class="card-body">
                    @if ($leadTotal === 0)
                        <p class="text-muted text-center py-5 mb-0">No contact activity yet. Calls, WhatsApp and location taps will appear here.</p>
                    @else
                        <div class="d-flex align-items-end" style="gap:6px; height:180px;">
                            @foreach ($daily as $date => $count)
                                <div class="flex-fill d-flex flex-column align-items-center justify-content-end" style="height:100%;" title="{{ \Carbon\Carbon::parse($date)->format('d M') }}: {{ $count }}">
                                    <span class="small text-muted" style="font-size:10px;">{{ $count ?: '' }}</span>
                                    <div style="width:100%; max-width:22px; border-radius:4px 4px 0 0; background:#4f46e5;
                                        height:{{ max(2, round($count / $maxDaily * 150)) }}px;"></div>
                                    <span class="text-muted" style="font-size:9px;">{{ \Carbon\Carbon::parse($date)->format('d') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Lead type breakdown --}}
        <div class="col-lg-5 mb-3">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">How they reached out</h5></div>
                <div class="card-body">
                    @php $labels = ['call'=>'Calls','whatsapp'=>'WhatsApp','direction'=>'Directions','website'=>'Website']; @endphp
                    @foreach ($labels as $t => $lbl)
                        @php $c = (int) ($byType[$t] ?? 0); $pct = $leadTotal ? round($c / $leadTotal * 100) : 0; @endphp
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small"><span>{{ $lbl }}</span><span class="text-muted">{{ $c }}</span></div>
                            <div class="progress" style="height:8px;">
                                <div class="progress-bar bg-primary" style="width:{{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Review sentiment --}}
    <div class="card mb-3">
        <div class="card-header"><h5 class="card-title mb-0">What customers feel <small class="text-muted">— from their star ratings</small></h5></div>
        <div class="card-body">
            @if ($sentTotal === 0)
                <p class="text-muted mb-0">No reviews yet. Sentiment appears here once customers rate you.</p>
            @else
                <div class="progress mb-2" style="height:16px;">
                    @foreach ($sent as $k => $c)
                        @php $pct = round($c / $sentTotal * 100); @endphp
                        @if ($c > 0)
                            <div class="progress-bar bg-{{ $sentColors[$k] }}" style="width:{{ $pct }}%" title="{{ ucfirst($k) }}: {{ $c }}">{{ $pct }}%</div>
                        @endif
                    @endforeach
                </div>
                <div class="d-flex flex-wrap small text-muted" style="gap:16px;">
                    <span><i class="tio-circle text-success"></i> Positive {{ $sent['positive'] }}</span>
                    <span><i class="tio-circle text-secondary"></i> Neutral {{ $sent['neutral'] }}</span>
                    <span><i class="tio-circle text-danger"></i> Negative {{ $sent['negative'] }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Trust badges --}}
    @if (!empty($badges))
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Your trust badges</h5></div>
            <div class="card-body d-flex flex-wrap" style="gap:8px;">
                @foreach ($badges as $b)
                    <span class="badge badge-soft-success p-2"><i class="tio-verified"></i> {{ $b['label'] }}</span>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
