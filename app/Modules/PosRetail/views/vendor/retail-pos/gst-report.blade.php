@extends('layouts.vendor.app')

@section('title', 'GST Report')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    @include('posretail::vendor.retail-pos._styles')
@endpush

@section('content')
    <div class="content container-fluid rp">
        <div class="rp-head">
            <div>
                <h1>GST Report</h1>
                <div class="sub">GSTR-1 / 3B summary by tax slab · {{ $from }} → {{ $to }}</div>
            </div>
            <a href="{{ route('vendor.retail-pos.gst-report', ['from' => $from, 'to' => $to, 'branch' => $branch, 'export' => 'csv']) }}"
                class="rp-btn p">⬇ Export CSV</a>
        </div>

        <div class="rp-card">
            <div class="bd">
                <form method="get" class="rp-filter date-range-form" action="{{ route('vendor.retail-pos.gst-report') }}">
                    @if ($branches->count())
                        <select name="branch" class="rp-input" onchange="this.form.submit()">
                            <option value="">All branches</option>
                            <option value="main" {{ $branch === 0 ? 'selected' : '' }}>Main Store</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ $branch == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    @include('vendor-views.form_modals.date_range')
                    <button type="button" class="btn btn-outline-warning btn-sm" data-toggle="modal" data-target="#dateRangeModal">
                         {{ translate($preset) }} 
                    </button>
                </form>
            </div>
        </div>

        <div class="rp-card">
            <div class="hd"><span class="accent">Tax summary</span></div>
            <div class="table-responsive">
                <table class="rp-table">
                    <thead>
                        <tr>
                            <th>GST Rate</th><th class="text-right">Taxable Value</th>
                            <th class="text-right">CGST</th><th class="text-right">SGST</th>
                            <th class="text-right">Total Tax</th><th class="text-right">Invoice Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $tTaxable = 0; $tTax = 0; @endphp
                        @forelse ($rows as $r)
                            @php $tax = round($r->tax, 2); $tTaxable += $r->taxable; $tTax += $tax; @endphp
                            <tr>
                                <td><span class="rp-badge muted">{{ rtrim(rtrim(number_format((float) $r->rate, 2), '0'), '.') }}%</span></td>
                                <td class="text-right">₹{{ number_format((float) $r->taxable, 2) }}</td>
                                <td class="text-right">₹{{ number_format($tax / 2, 2) }}</td>
                                <td class="text-right">₹{{ number_format($tax / 2, 2) }}</td>
                                <td class="text-right">₹{{ number_format($tax, 2) }}</td>
                                <td class="text-right font-weight-bold">₹{{ number_format((float) $r->taxable + $tax, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="rp-empty">No sales in this period.</div></td></tr>
                        @endforelse
                    </tbody>
                    @if (count($rows))
                        <tfoot>
                            <tr>
                                <td>Total</td>
                                <td class="text-right">₹{{ number_format($tTaxable, 2) }}</td>
                                <td class="text-right">₹{{ number_format($tTax / 2, 2) }}</td>
                                <td class="text-right">₹{{ number_format($tTax / 2, 2) }}</td>
                                <td class="text-right">₹{{ number_format($tTax, 2) }}</td>
                                <td class="text-right">₹{{ number_format($tTaxable + $tTax, 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    @include('vendor-views.js.date_range')
@endpush
