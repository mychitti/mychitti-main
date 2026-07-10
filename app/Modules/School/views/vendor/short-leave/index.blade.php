@extends('layouts.vendor.app')
@section('title', 'Short Leave / Gate Pass')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-exit-to-app mr-1"></i> Short Leave / Gate Pass</h1>
        <div class="d-flex align-items-center" style="gap:8px;">
            <form method="GET"><input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm" onchange="this.form.submit()"></form>
            @if(hasPermission("short_leave","add"))<a href="{{ route('vendor.school.short-leave.create') }}" class="btn btn-sm btn--primary"><i class="tio-add"></i> Issue Pass</a>@endif
        </div>
    </div>
 
    <div class="row">
        @php $tiles = ['total' => ['Passes Today', 'indigo'], 'out' => ['Currently Out', 'amber'], 'returned' => ['Returned', 'green']]; @endphp
        @foreach($tiles as $k => $meta)
            <div class="col-sm-4 mb-3">
                <a href="{{ route('vendor.school.short-leave.index', ['date' => $date, 'status' => $k === 'total' ? null : $k]) }}"
                   class="sch-stat sch-stat--{{ $meta[1] }} d-block {{ ($status === $k || ($k==='total' && !$status)) ? 'border border-dark' : '' }}" style="text-decoration:none;">
                    <div class="sch-stat-label">{{ $meta[0] }}</div>
                    <div class="sch-stat-value">{{ $counts[$k] }}</div>
                </a>
            </div>
        @endforeach
    </div>

    @if(hasPermission("short_leave","view"))<div class="card mb-3"><div class="card-body d-flex flex-wrap align-items-center" style="gap:8px;">
        <form method="GET" class="input-group input-group-merge" style="max-width:360px;">
            <input type="hidden" name="date" value="{{ $date }}">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input name="search" value="{{ $search }}" class="form-control" placeholder="Search student name / admission no">
            <button class="btn btn--primary">Search</button>
        </form>
        @if($status || $search)<a href="{{ route('vendor.school.short-leave.index', ['date' => $date]) }}" class="btn btn-outline-secondary">Clear</a>@endif
    </div></div>@endif

    @if(hasPermission("short_leave","view"))<div class="card"><div class="card-body p-0"><div class="table-responsive">
        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
            <thead class="thead-light"><tr>
                <th>Pass No</th><th>Student</th><th>Class</th><th>Out</th><th>Return</th><th>Taken By</th><th>Status</th><th class="text-right">Action</th>
            </tr></thead>
            <tbody>
            @forelse($passes as $p)
                <tr>
                    <td class="font-weight-bold">{{ $p->gate_pass_no }}</td>
                    <td>{{ $p->student?->name ?? '—' }}<br><small class="text-muted">{{ $p->student?->admission_no }}</small></td>
                    <td>{{ $p->student?->currentEnrollment?->schoolClass?->name ?? '—' }}</td>
                    <td>{{ $p->out_time }}@if($p->reason)<br><small class="text-muted">{{ $p->reason }}</small>@endif</td>
                    <td>{{ $p->return_time ?? ($p->is_returning ? '—' : 'Not returning') }}</td>
                    <td><small>{{ $p->taken_by ?? '—' }}@if($p->taken_by_relation) ({{ $p->taken_by_relation }})@endif</small></td>
                    <td><span class="badge badge-soft-{{ $p->status === 'returned' ? 'success' : 'warning' }}">{{ $p->status === 'returned' ? 'Returned' : 'Out' }}</span></td>
                    <td class="text-right">
                        <div class="dropdown sch-actions">
                            <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="{{ route('vendor.school.short-leave.slip', $p->id) }}" target="_blank"><i class="tio-print"></i> Print Slip</a>
                                @if($p->status !== 'returned' && $p->is_returning)
                                    @if(hasPermission("short_leave","return"))<a class="dropdown-item text-success" href="javascript:;" onclick="markReturn({{ $p->id }})"><i class="tio-checkmark-circle"></i> Mark Returned</a>@endif
                                @endif
                                @if(hasPermission("short_leave","delete"))<a class="dropdown-item text-danger" href="{{ route('vendor.school.short-leave.delete', $p->id) }}" onclick="return confirm('Delete this gate pass?')"><i class="tio-delete"></i> Delete</a>@endif
                            </div>
                        </div>
                        <form id="ret-{{ $p->id }}" action="{{ route('vendor.school.short-leave.return', $p->id) }}" method="POST" class="d-none">@csrf<input type="hidden" name="return_time" class="rt-time"></form>
                    </td>
                </tr>
            @empty<tr><td colspan="8" class="text-center text-muted py-5">No gate passes for this date.</td></tr>@endforelse
            </tbody>
        </table>
    </div></div></div>@endif
    @if(hasPermission("short_leave","view") && count($passes))<div class="mt-3 px-2">{!! $passes->links() !!}</div>@endif
</div>
@endsection

@push('script_2')
<script>
function markReturn(id) {
    var now = new Date().toTimeString().slice(0, 5);
    var t = prompt('Return time (HH:MM):', now);
    if (t === null) return;
    var f = document.getElementById('ret-' + id);
    f.querySelector('.rt-time').value = t.trim() || now;
    f.submit();
}
</script>
@endpush
