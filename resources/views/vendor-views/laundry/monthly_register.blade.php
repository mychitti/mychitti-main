@extends('layouts.vendor.app')
@section('title', 'Monthly Register')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
<style>
    .register-table th, .register-table td { padding: 4px 6px; font-size: 11px; text-align: center; white-space: nowrap; }
    .register-table td.item-col { text-align: left; min-width: 130px; font-weight: 600; }
    .register-table td.zero { color: #ccc; }
    .register-table thead th { background: #1e3a8a; color: #fff; }
    .register-table tfoot td { background: #f0f0f0; font-weight: bold; }
    .overflow-x { overflow-x: auto; }

    @media print {
        @page { size: A4 landscape; margin: 8mm; }
        body * { visibility: hidden; }
        .print-area, .print-area * { visibility: visible; }
        .print-area { position: absolute; top: 0; left: 0; width: 100%; }
        .register-table th, .register-table td { padding: 2px 3px !important; font-size: 8px !important; }
        .register-table td.item-col { min-width: 80px !important; }
        .register-table thead th { background: #1e3a8a !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .register-table tfoot td { background: #f0f0f0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .register-table td.zero { color: #bbb !important; }
        .overflow-x { overflow: visible !important; }
        .card { border: 1px solid #ddd !important; box-shadow: none !important; }
        .card-header { padding: 4px 8px !important; }
        .card-title { font-size: 11px !important; margin: 0 !important; }
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex align-items-center justify-content-between">
        <h1 class="page-header-title"><span class="page-header-icon"><i class="tio-calendar"></i></span> Monthly Register</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('vendor.laundry.register.export', array_filter(['date_range' => $preset, 'custom_date_range' => request('custom_date_range'), 'hotel_id' => $hotelId])) }}"
               class="btn btn-outline-success"><i class="tio-download"></i> Export Excel</a>
            <button onclick="window.print()" class="btn btn-outline-secondary"><i class="tio-print"></i> Print</button>
            @if($hotelId)
            <a href="{{ route('vendor.laundry.register.billing', array_filter(['hotel_id' => $hotelId, 'date_range' => $preset, 'custom_date_range' => request('custom_date_range')])) }}"
               class="btn btn--primary"><i class="tio-receipt"></i> Generate Bill</a>
            @else
            <button class="btn btn--primary" disabled title="Select a hotel to generate bill">
                <i class="tio-receipt"></i> Generate Bill
            </button>
            @endif
        </div>
    </div>

    {{-- Info banner --}}
    @if(!$hotelId)
    <div class="alert alert-soft-info d-flex align-items-center gap-2 py-2 mb-3" style="font-size:13px;">
        <i class="tio-info-outined" style="font-size:18px; flex-shrink:0;"></i>
        <span>
            To generate a <strong>monthly bill</strong> for a hotel: <strong>1)</strong> Select a hotel from the dropdown below,
            <strong>2)</strong> Choose the billing period, <strong>3)</strong> Click <strong>Generate Bill</strong> — you can edit prices before the invoice is created.
        </span>
    </div>
    @endif

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row">
                <form class="col-md-3">
                    <select name="hotel_id" class="form-control js-select2-custom" onchange="this.form.submit()">
                        <option value="">All Hotels</option>
                        @foreach($hotels as $h)
                            <option value="{{ $h->id }}" {{ $hotelId == $h->id ? 'selected' : '' }}>{{ $h->f_name }}</option>
                        @endforeach
                    </select>
                </form>
                <form class="col-md-3 date-range-form">
                    <button style="width:100%;white-space:nowrap" class="btn btn-outline-warning" type="button"
                        data-toggle="modal" data-target="#dateRangeModal">
                        {{ translate($preset) }}
                    </button>
                    @include('vendor-views/form_modals/date_range')
                </form>
            </div>
        </div>
    </div>

    @php
        $singleMonth = \Carbon\Carbon::parse($from)->format('Y-m') === \Carbon\Carbon::parse($to)->format('Y-m');
    @endphp

    <div class="print-area">
        {{-- Print-only header --}}
        <div class="d-none d-print-block mb-2" style="font-size:12px; font-weight:bold;">
            Laundry Register — {{ \Carbon\Carbon::parse($from)->format('d M Y') }} to {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
            @if($hotelId) — {{ $hotels->firstWhere('id', $hotelId)->f_name ?? '' }} @endif
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    Sent Qty —
                    {{ \Carbon\Carbon::parse($from)->format('d M Y') }}
                    @if($from !== $to) to {{ \Carbon\Carbon::parse($to)->format('d M Y') }} @endif
                    @if($hotelId) — {{ $hotels->firstWhere('id', $hotelId)->f_name ?? '' }} @endif
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="overflow-x">
                    <table class="table table-bordered register-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-left">Item</th>
                                @foreach($dates as $date)
                                    <th>{{ $singleMonth ? (int)\Carbon\Carbon::parse($date)->format('j') : \Carbon\Carbon::parse($date)->format('d M') }}</th>
                                @endforeach
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($grid as $itemName => $days)
                            @php $rowTotal = array_sum($days); @endphp
                            <tr>
                                <td class="item-col">{{ $itemName }}</td>
                                @foreach($dates as $date)
                                    <td class="{{ ($days[$date] ?? 0) == 0 ? 'zero' : '' }}">
                                        {{ ($days[$date] ?? 0) ?: '' }}
                                    </td>
                                @endforeach
                                <td><strong>{{ $rowTotal }}</strong></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ count($dates) + 2 }}" class="text-center py-4">No data for this period</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(count($grid) > 0)
                        <tfoot>
                            <tr>
                                <td class="text-left">Daily Total</td>
                                @php $grandTotal = 0; @endphp
                                @foreach($dates as $date)
                                    @php $dayTotal = array_sum(array_column(array_map(fn($r) => [$r[$date] ?? 0], $grid), 0)); $grandTotal += $dayTotal; @endphp
                                    <td>{{ $dayTotal ?: '' }}</td>
                                @endforeach
                                <td>{{ $grandTotal }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
@endpush
