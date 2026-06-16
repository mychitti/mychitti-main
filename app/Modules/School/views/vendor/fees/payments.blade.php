@extends('layouts.vendor.app')
@section('title', 'Fee Collection Report')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-chart-bar-1 mr-1"></i> Fee Collection</h1>
        <a href="{{ route('vendor.school.fees.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    @if(hasPermission("fee_collection","view"))<div class="card mb-3"><div class="card-body">
        <form method="GET" class="form-row align-items-end">
            <div class="col-md-3"><label class="input-label">From</label><input type="date" name="from" class="form-control" value="{{ $from }}"></div>
            <div class="col-md-3"><label class="input-label">To</label><input type="date" name="to" class="form-control" value="{{ $to }}"></div>
            <div class="col-md-2"><button class="btn btn--primary">Filter</button></div>
            <div class="col-md-4 text-right"><span class="text-muted">Total collected: </span>
                <strong class="text-success h5">{{ \App\CentralLogics\Helpers::format_currency($total) }}</strong></div>
        </form>
    </div></div>@endif

    @if(hasPermission("fee_collection","view"))<div class="card"><div class="card-body p-0"><div class="table-responsive">
        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
            <thead class="thead-light"><tr><th>Receipt</th><th>Date</th><th>Student</th><th>Mode</th><th>Collected By</th><th class="text-right">Amount</th></tr></thead>
            <tbody>
            @forelse($payments as $p)
                <tr>
                    <td><a href="{{ route('vendor.school.fees.receipt', $p->fee_invoice_id) }}" target="_blank">{{ $p->receipt_no }}</a></td>
                    <td>{{ $p->paid_on?->format('d M Y') }}</td>
                    <td class="font-weight-bold">{{ $p->student?->name }}<br><small class="text-muted">{{ $p->student?->admission_no }}</small></td>
                    <td>{{ $p->payment_mode }}</td>
                    <td>{{ $p->collected_by }}</td>
                    <td class="text-right font-weight-bold text-success">{{ \App\CentralLogics\Helpers::format_currency($p->amount) }}</td>
                </tr>
            @empty<tr><td colspan="6" class="text-center text-muted py-5">No collections in this range.</td></tr>@endforelse
            </tbody>
        </table>
    </div></div></div>@endif
    @if(hasPermission("fee_collection","view") && count($payments))<div class="mt-3 px-2">{!! $payments->links() !!}</div>@endif
</div>
@endsection
