@extends('layouts.admin.app')
@section('title', translate('Zone Performance Report'))

@push('css_or_js')
<style>
    .rate-pill { display:inline-block; padding:2px 8px; border-radius:10px; font-size:.75rem; font-weight:600; color:#fff; }
    .tbl-section-header td { background:#f8f9fa; font-weight:700; font-size:.75rem; text-transform:uppercase; color:#677788; letter-spacing:.04em; padding:6px 12px; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-header-title">{{ translate('Zone Performance Report') }}</h1>
                <ol class="breadcrumb breadcrumb-no-gutter">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ translate('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ translate('Zone Report') }}</li>
                </ol>
            </div>
        </div>
    </div>

    {{-- Main zone table --}}
    <div class="card mb-4">
        <div class="card-header py-2"><h6 class="mb-0">{{ translate('Zone Summary') }}</h6></div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-align-middle table-nowrap mb-0" style="font-size:.83rem">
                <thead class="thead-light">
                    <tr>
                        <th rowspan="2" class="align-middle">{{ translate('Zone') }}</th>
                        <th colspan="4" class="text-center border-left">{{ translate('Sales Queries') }}</th>
                        <th colspan="3" class="text-center border-left">{{ translate('Follow-ups') }}</th>
                        <th colspan="3" class="text-center border-left">{{ translate('Support Tickets') }}</th>
                    </tr>
                    <tr>
                        <th class="border-left">{{ translate('Total') }}</th>
                        <th>{{ translate('Pipeline') }}</th>
                        <th>{{ translate('Won') }}</th>
                        <th>{{ translate('Conv. %') }}</th>
                        <th class="border-left">{{ translate('Done') }}</th>
                        <th>{{ translate('Pending') }}</th>
                        <th>{{ translate('Overdue') }}</th>
                        <th class="border-left">{{ translate('Total') }}</th>
                        <th>{{ translate('Resolved') }}</th>
                        <th>{{ translate('Res. %') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report as $row)
                    <tr>
                        <td class="font-weight-bold">{{ $row['zone'] }}</td>
                        <td>{{ $row['q_total'] }}</td>
                        <td>{{ $row['q_pipeline'] }}</td>
                        <td class="text-success font-weight-bold">{{ $row['q_converted'] }}</td>
                        <td>
                            @php $cr = $row['conv_rate']; @endphp
                            <span class="rate-pill" style="background:{{ $cr >= 50 ? '#28a745' : ($cr >= 25 ? '#fd7e14' : '#dc3545') }}">
                                {{ $cr }}%
                            </span>
                        </td>
                        <td class="border-left text-success">{{ $row['fu_done'] }}</td>
                        <td>{{ $row['fu_pending'] }}</td>
                        <td class="{{ $row['fu_overdue'] > 0 ? 'text-danger font-weight-bold' : '' }}">{{ $row['fu_overdue'] }}</td>
                        <td class="border-left">{{ $row['tk_total'] }}</td>
                        <td class="text-success">{{ $row['tk_resolved'] }}</td>
                        <td>
                            @php $rr = $row['tk_resolve_rate']; @endphp
                            <span class="rate-pill" style="background:{{ $rr >= 70 ? '#28a745' : ($rr >= 40 ? '#fd7e14' : '#6c757d') }}">
                                {{ $rr }}%
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="text-center py-4 text-muted">{{ translate('No data.') }}</td></tr>
                    @endforelse
                </tbody>
                @if($report->count() > 1)
                <tfoot class="thead-light font-weight-bold">
                    <tr>
                        <td>{{ translate('Total') }}</td>
                        <td>{{ $report->sum('q_total') }}</td>
                        <td>{{ $report->sum('q_pipeline') }}</td>
                        <td>{{ $report->sum('q_converted') }}</td>
                        <td>
                            @php $t = $report->sum('q_total'); $c = $report->sum('q_converted'); $pct = $t > 0 ? round($c/$t*100) : 0; @endphp
                            <span class="rate-pill" style="background:{{ $pct >= 50 ? '#28a745' : ($pct >= 25 ? '#fd7e14' : '#dc3545') }}">{{ $pct }}%</span>
                        </td>
                        <td>{{ $report->sum('fu_done') }}</td>
                        <td>{{ $report->sum('fu_pending') }}</td>
                        <td class="{{ $report->sum('fu_overdue') > 0 ? 'text-danger' : '' }}">{{ $report->sum('fu_overdue') }}</td>
                        <td>{{ $report->sum('tk_total') }}</td>
                        <td>{{ $report->sum('tk_resolved') }}</td>
                        <td>
                            @php $tt = $report->sum('tk_total'); $tr = $report->sum('tk_resolved'); $rp = $tt > 0 ? round($tr/$tt*100) : 0; @endphp
                            <span class="rate-pill" style="background:{{ $rp >= 70 ? '#28a745' : ($rp >= 40 ? '#fd7e14' : '#6c757d') }}">{{ $rp }}%</span>
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Lost reason breakdown --}}
    @if($lostReasons->count())
    <div class="card">
        <div class="card-header py-2"><h6 class="mb-0">{{ translate('Lost Reason Breakdown') }}</h6></div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-nowrap mb-0" style="font-size:.83rem; max-width:500px">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Reason') }}</th>
                        <th>{{ translate('Count') }}</th>
                        <th>{{ translate('Share') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $lostTotal = $lostReasons->sum('total'); @endphp
                    @foreach($lostReasons as $lr)
                    <tr>
                        <td>{{ ucfirst(str_replace('_', ' ', $lr->lost_reason)) }}</td>
                        <td>{{ $lr->total }}</td>
                        <td>
                            @php $pct = $lostTotal > 0 ? round($lr->total / $lostTotal * 100) : 0; @endphp
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:80px; background:#e9ecef; border-radius:4px; height:8px; overflow:hidden">
                                    <div style="width:{{ $pct }}%; background:#dc3545; height:100%"></div>
                                </div>
                                <span>{{ $pct }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
