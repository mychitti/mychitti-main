@extends('layouts.vendor.app')
@section('title', 'Fees')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-money mr-1"></i> Fees — Dues</h1>
        <div class="d-flex" style="gap:8px;">
            @if(hasPermission('fee_heads','view'))<a href="{{ route('vendor.school.fees.heads') }}" class="btn btn-sm btn-outline-secondary">Fee Heads</a>@endif
            @if(hasPermission('fee_structure','view'))<a href="{{ route('vendor.school.fees.structure') }}" class="btn btn-sm btn-outline-secondary">Fee Structure</a>@endif
            @if(hasPermission('scholarship','view'))<a href="{{ route('vendor.school.fees.concessions') }}" class="btn btn-sm btn-outline-success">Scholarships</a>@endif
            @if(hasPermission('fee_collection','view'))<a href="{{ route('vendor.school.fees.payments') }}" class="btn btn-sm btn-outline-info">Collection Report</a>@endif
            @if(hasPermission('fee_collection','collect'))<a href="{{ route('vendor.school.students.index') }}" class="btn btn-sm btn--primary"><i class="tio-add"></i> Collect Fee</a>@endif
        </div>
    </div>

    @if(hasPermission("fee_dues","view"))<div class="card mb-3"><div class="card-body">
        <form method="GET" class="input-group input-group-merge">
            <input name="search" value="{{ $search }}" class="form-control" placeholder="Search student name / admission no with dues">
            <button class="btn btn--primary">Search</button>
        </form>
    </div></div>@endif

    @if(hasPermission("fee_dues","view"))<div class="card"><div class="card-body p-0"><div class="table-responsive">
        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
            <thead class="thead-light"><tr>
                <th>Invoice</th><th>Student</th><th>Date</th><th class="text-right">Total</th><th class="text-right">Paid</th><th class="text-right">Due</th><th class="text-right">Action</th>
            </tr></thead>
            <tbody>
            @forelse($dues as $d)
                <tr>
                    <td>{{ $d->invoice_no }}</td>
                    <td class="font-weight-bold">{{ $d->student?->name }}<br><small class="text-muted">{{ $d->student?->admission_no }}</small></td>
                    <td>{{ $d->invoice_date?->format('d M Y') }}</td>
                    <td class="text-right">{{ \App\CentralLogics\Helpers::format_currency($d->total_amount) }}</td>
                    <td class="text-right">{{ \App\CentralLogics\Helpers::format_currency($d->paid_amount) }}</td>
                    <td class="text-right"><span class="badge badge-soft-danger">{{ \App\CentralLogics\Helpers::format_currency($d->due_amount) }}</span></td>
                    <td class="text-right">
                        <div class="dropdown sch-actions">
                            <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                            <div class="dropdown-menu dropdown-menu-right">
                                @if(hasPermission('fee_collection','collect'))<a class="dropdown-item text-success" href="{{ route('vendor.school.fees.collect', $d->student_id) }}"><i class="tio-money"></i> Collect Fee</a>@endif
                                <a class="dropdown-item" href="{{ route('vendor.school.fees.receipt', $d->id) }}" target="_blank"><i class="tio-receipt"></i> View Receipt</a>
                                <a class="dropdown-item text-warning" href="{{ route('vendor.school.fees.reminder', $d->id) }}"><i class="tio-send"></i> Send Reminder</a>
                            </div>
                        </div>
                    </td> 
                </tr>
            @empty<tr><td colspan="7" class="text-center text-muted py-5">No outstanding dues. 🎉</td></tr>@endforelse
            </tbody>
        </table>
    </div></div></div>@endif
    @if(hasPermission("fee_dues","view") && count($dues))<div class="mt-3 px-2">{!! $dues->links() !!}</div>@endif
</div>
@endsection
