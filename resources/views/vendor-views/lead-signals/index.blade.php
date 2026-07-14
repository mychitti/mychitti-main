@extends('layouts.vendor.app')

@section('title', 'Lead Inbox')

@php
    // Soft, pre-conversion signals only. Bookings/quotes are managed in their own screens.
    $meta = [ 
        'call'      => ['label' => 'Calls',      'icon' => 'tio-call',   'color' => 'success'],
        'whatsapp'  => ['label' => 'WhatsApp',   'icon' => 'tio-chat',   'color' => 'success'],
        'direction' => ['label' => 'Directions', 'icon' => 'tio-poi',    'color' => 'info'],
        'website'   => ['label' => 'Website',    'icon' => 'tio-globe',  'color' => 'secondary'],
    ];
@endphp 

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="page-header-title mb-0">Lead Inbox</h1>
            <p class="mb-0">Customers who showed interest — called, messaged or checked your location — but haven't booked yet. Follow up before they go elsewhere.</p>
        </div>
        <div class="btn-group btn-group-sm" role="group">
            @foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days', 365 => '1 year'] as $d => $lbl)
                <a href="{{ route('vendor.lead-signals.index', ['days' => $d]) }}"
                   class="btn btn-{{ $days == $d ? 'primary' : 'outline-primary' }}">{{ $lbl }}</a>
            @endforeach
        </div>
    </div>

    {{-- Compact summary strip — intentionally small; the follow-up feed below is the focus.
         (Aggregate marketing metrics live in Performance Analytics, not here.) --}}
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center" style="gap:22px;">
            <div>
                <span class="d-block text-muted small">Total leads</span>
                <span class="h2 mb-0">{{ $total }}</span>
            </div>
            <div class="d-flex flex-wrap" style="gap:18px;">
                @foreach ($meta as $type => $m)
                    <div class="text-center">
                        <span class="d-block text-{{ $m['color'] }}"><i class="{{ $m['icon'] }}"></i></span>
                        <span class="h5 mb-0">{{ $byType[$type] ?? 0 }}</span>
                        <span class="d-block text-muted" style="font-size:11px;">{{ $m['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Follow up — recent leads</h5></div>
        <div class="table-responsive">
            <table class="table table-align-middle">
                <thead class="thead-light">
                    <tr>
                        <th>Customer</th>
                        <th>What they did</th>
                        <th>When</th>
                        <th class="text-right">Follow up</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $row)
                        @php
                            $m = $meta[$row->type] ?? ['label' => ucfirst($row->type), 'color' => 'secondary', 'icon' => 'tio-circle'];
                            $wa = $row->phone ? store_whatsapp_link($row->phone) : null;
                        @endphp
                        <tr>
                            <td>
                                @if ($row->f_name)
                                    <strong>{{ trim($row->f_name . ' ' . $row->l_name) }}</strong>
                                    @if ($row->phone)<div class="text-muted small">{{ $row->phone }}</div>@endif
                                @else
                                    <span class="text-muted">Guest</span>
                                    @if ($row->phone)<div class="text-muted small">{{ $row->phone }}</div>@endif
                                @endif
                            </td>
                            <td><span class="badge badge-soft-{{ $m['color'] }}"><i class="{{ $m['icon'] }}"></i> {{ $m['label'] }}</span></td>
                            <td class="small">{{ \Carbon\Carbon::parse($row->created_at)->diffForHumans() }}
                                <div class="text-muted">{{ \Carbon\Carbon::parse($row->created_at)->format('d M, h:i A') }}</div>
                            </td>
                            <td class="text-right">
                                @if ($row->phone)
                                    <a href="tel:{{ $row->phone }}" class="btn btn-sm btn-outline-primary" title="Call back">
                                        <i class="tio-call"></i> Call
                                    </a>
                                    @if ($wa)
                                        <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success" title="Message on WhatsApp">
                                            <i class="tio-chat"></i> WhatsApp
                                        </a>
                                    @endif
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-5">
                            No leads in this period yet. When a customer calls, messages, or books, they'll show here to follow up.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $recent->appends(['days' => $days])->links() }}</div>
    </div>
</div>
@endsection
