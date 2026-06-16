@extends('layouts.vendor.app')
@section('title', 'Fee Structure')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-poll mr-1"></i> Fee Structure</h1>
        <a href="{{ route('vendor.school.fees.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="form-row align-items-end">
            <div class="col-md-4"><label class="input-label">Class</label>
                <select name="class_id" class="form-control js-select2-custom" required onchange="this.form.submit()">
                    <option value="">Select class</option>
                    @foreach($classes as $c)<option value="{{ $c->id }}" {{ (string)$classId===(string)$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
                </select></div>
            <div class="col-md-4"><label class="input-label">Session</label>
                <select name="session_id" class="form-control js-select2-custom" onchange="this.form.submit()">
                    @foreach($sessions as $se)<option value="{{ $se->id }}" {{ (string)$sessionId===(string)$se->id?'selected':'' }}>{{ $se->name }}</option>@endforeach
                </select></div>
        </form>
    </div></div>

    @if($classId)
    @if(count($heads))
    <form action="{{ route('vendor.school.fees.structure.save') }}" method="POST">
        @csrf
        <input type="hidden" name="class_id" value="{{ $classId }}">
        <input type="hidden" name="session_id" value="{{ $sessionId }}">
        <div class="card"><div class="card-body p-0"><div class="table-responsive">
            <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                <thead class="thead-light"><tr><th>Fee Head</th><th style="width:200px;">Amount</th><th style="width:200px;">Frequency</th></tr></thead>
                <tbody>
                @foreach($heads as $h)
                    <tr>
                        <td class="font-weight-bold">{{ $h->name }} @if($h->gst_percent>0)<span class="badge badge-soft-info">GST {{ rtrim(rtrim(number_format($h->gst_percent,2),'0'),'.') }}%</span>@endif</td>
                        <td><input type="number" step="0.01" min="0" name="amount[{{ $h->id }}]" class="form-control form-control-sm" value="{{ $items->get($h->id)['amount'] ?? '' }}"></td>
                        <td>
                            <select name="frequency[{{ $h->id }}]" class="form-control form-control-sm">
                                @foreach(['Monthly','Quarterly','Annual','One-time'] as $f)
                                    <option value="{{ $f }}" {{ ($items->get($h->id)['frequency'] ?? 'Annual')===$f?'selected':'' }}>{{ $f }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div></div>
        <div class="card-footer text-right">@if(hasPermission('fee_structure','add'))<button class="btn btn--primary"><i class="tio-save"></i> Save Structure</button>@endif</div>
        </div>
    </form>
    @else
        <div class="alert alert-soft-warning">Add <a href="{{ route('vendor.school.fees.heads') }}">fee heads</a> first.</div>
    @endif
    @endif
</div>
@endsection
