@extends('layouts.vendor.app')

@section('title', 'Lead Inbox')

@php 
    $meta = [
        'call'      => ['label' => 'Calls',      'icon' => 'tio-call',              'color' => 'success'],
        'whatsapp'  => ['label' => 'WhatsApp',   'icon' => 'tio-chat',              'color' => 'success'],
        'booking'   => ['label' => 'Bookings',   'icon' => 'tio-calendar',          'color' => 'primary'],
        'quote'     => ['label' => 'Quotes',     'icon' => 'tio-receipt-outlined',  'color' => 'warning'],
        'direction' => ['label' => 'Directions', 'icon' => 'tio-poi',               'color' => 'info'],
        'website'   => ['label' => 'Website',    'icon' => 'tio-globe',             'color' => 'secondary'],
    ];
@endphp

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="page-header-title mb-0">Lead Inbox</h1>
            <p class="mb-0">Contact actions customers took on your listing — calls, WhatsApp, bookings and more.</p>
        </div>
        <div class="btn-group btn-group-sm" role="group">
            @foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days', 365 => '1 year'] as $d => $lbl)
                <a href="{{ route('vendor.lead-signals.index', ['days' => $d]) }}"
                   class="btn btn-{{ $days == $d ? 'primary' : 'outline-primary' }}">{{ $lbl }}</a>
            @endforeach
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6 col-lg-2 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <span class="d-block text-muted small">Total leads</span>
                    <span class="h2 mb-0">{{ $total }}</span>
                </div>
            </div>
        </div>
        @foreach ($meta as $type => $m)
            <div class="col-sm-6 col-lg-2 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="{{ $m['icon'] }} text-{{ $m['color'] }}"></i>
                        <span class="d-block text-muted small mt-1">{{ $m['label'] }}</span>
                        <span class="h4 mb-0">{{ $byType[$type] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Recent activity</h5></div>
        <div class="table-responsive">
            <table class="table table-align-middle">
                <thead class="thead-light">
                    <tr>
                        <th>When</th>
                        <th>Action</th>
                        <th>Customer</th>
                        <th>Source</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $row)
                        @php $m = $meta[$row->type] ?? ['label' => ucfirst($row->type), 'color' => 'secondary', 'icon' => 'tio-circle']; @endphp
                        <tr>
                            <td class="small">{{ \Carbon\Carbon::parse($row->created_at)->diffForHumans() }}
                                <div class="text-muted">{{ \Carbon\Carbon::parse($row->created_at)->format('d M, h:i A') }}</div>
                            </td>
                            <td><span class="badge badge-soft-{{ $m['color'] }}"><i class="{{ $m['icon'] }}"></i> {{ $m['label'] }}</span></td>
                            <td>
                                @if ($row->f_name)
                                    {{ trim($row->f_name . ' ' . $row->l_name) }}
                                    @if ($row->phone)<div class="text-muted small">{{ $row->phone }}</div>@endif
                                @else
                                    <span class="text-muted">Guest</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $row->utm_source ?: ($row->source ?: 'web') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No lead activity in this period yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $recent->appends(['days' => $days])->links() }}</div>
    </div>
</div>
@endsection
