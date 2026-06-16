@extends('layouts.vendor.app')
@section('title', 'Fee Heads')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-label mr-1"></i> Fee Heads</h1>
        <a href="{{ route('vendor.school.fees.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    <div class="card mb-3"><div class="card-body">
        <form action="{{ route('vendor.school.fees.heads.store') }}" method="POST" class="form-row align-items-end">
            @csrf
            <div class="col-md-5"><label class="input-label">Fee Head *</label>
                <input name="name" class="form-control" placeholder="Tuition / Transport / Exam / Library / Hostel" required></div>
            <div class="col-md-3"><label class="input-label">GST %</label>
                <input type="number" step="0.01" min="0" max="100" name="gst_percent" class="form-control" value="0"></div>
            <div class="col-md-2">@if(hasPermission('fee_heads','add'))<button class="btn btn--primary">Add Head</button>@endif</div>
        </form>
    </div></div>

    <div class="card"><div class="card-body p-0"><div class="table-responsive">
        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
            <thead class="thead-light"><tr><th>Fee Head</th><th>GST %</th><th class="text-right">Action</th></tr></thead>
            <tbody>
            @forelse($heads as $h)
                <tr>
                    <td class="font-weight-bold">{{ $h->name }}</td>
                    <td>{{ rtrim(rtrim(number_format($h->gst_percent, 2), '0'), '.') }}%</td>
                    <td class="text-right">
                        <div class="dropdown sch-actions">
                            <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                            <div class="dropdown-menu dropdown-menu-right">
                                @if(hasPermission('fee_heads','delete'))<a class="dropdown-item text-danger" href="{{ route('vendor.school.fees.heads.delete', $h->id) }}" onclick="return confirm('Delete this head?')"><i class="tio-delete"></i> Delete</a>@endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty<tr><td colspan="3" class="text-center text-muted py-4">No fee heads yet.</td></tr>@endforelse
            </tbody>
        </table>
    </div></div></div>
</div>
@endsection
