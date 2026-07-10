@extends('layouts.vendor.app')
@section('title', 'Student Leave')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-calendar-note mr-1"></i> Student Leave Requests</h1>
        @if(hasPermission("student_leave","add"))<a href="{{ route('vendor.school.student-leave.create') }}" class="btn btn-sm btn--primary"><i class="tio-add"></i> File Leave</a>@endif
    </div>
 
    {{-- Pipeline tiles (clickable filters) --}}
    <div class="row">
        @php $tiles = ['pending' => ['Pending', 'amber'], 'approved' => ['Approved', 'green'], 'rejected' => ['Rejected', 'indigo']]; @endphp
        @foreach($tiles as $k => $meta)
            <div class="col-sm-4 mb-3">
                <a href="{{ route('vendor.school.student-leave.index', ['status' => $k]) }}"
                   class="sch-stat sch-stat--{{ $meta[1] }} d-block {{ $status === $k ? 'border border-dark' : '' }}" style="text-decoration:none;">
                    <div class="sch-stat-label">{{ $meta[0] }}</div>
                    <div class="sch-stat-value">{{ $counts[$k] }}</div>
                </a>
            </div>
        @endforeach
    </div>

    @if(hasPermission("student_leave","view"))<div class="card mb-3"><div class="card-body d-flex flex-wrap align-items-center" style="gap:8px;">
        <form method="GET" class="input-group input-group-merge" style="max-width:380px;">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input name="search" value="{{ $search }}" class="form-control" placeholder="Search student name / admission no">
            <button class="btn btn--primary">Search</button>
        </form>
        @if($status || $search)<a href="{{ route('vendor.school.student-leave.index') }}" class="btn btn-outline-secondary">Clear</a>@endif
    </div></div>@endif

    @if(hasPermission("student_leave","view"))<div class="card"><div class="card-body p-0"><div class="table-responsive">
        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
            <thead class="thead-light"><tr>
                <th>Student</th><th>Class</th><th>Type</th><th>Dates</th><th class="text-center">Days</th><th>Status</th><th>Applied / Reviewed</th><th class="text-right">Action</th>
            </tr></thead>
            <tbody>
            @forelse($leaves as $l)
                <tr>
                    <td class="font-weight-bold">{{ $l->student?->name ?? '—' }}<br><small class="text-muted">{{ $l->student?->admission_no }}</small></td>
                    <td>{{ $l->student?->currentEnrollment?->schoolClass?->name ?? '—' }}</td>
                    <td>{{ $l->leave_type }}@if($l->reason)<br><small class="text-muted">{{ $l->reason }}</small>@endif</td>
                    <td>{{ $l->from_date?->format('d M') }} – {{ $l->to_date?->format('d M Y') }}</td>
                    <td class="text-center">{{ $l->days }}</td>
                    <td>
                        @php $badge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$l->status] ?? 'secondary'; @endphp
                        <span class="badge badge-soft-{{ $badge }}">{{ ucfirst($l->status) }}</span>
                        @if($l->status === 'rejected' && $l->remarks)<br><small class="text-muted">{{ $l->remarks }}</small>@endif
                    </td>
                    <td><small class="text-muted">{{ $l->applied_by }}@if($l->reviewed_by)<br>✓ {{ $l->reviewed_by }}@endif</small></td>
                    <td class="text-right">
                        <div class="dropdown sch-actions">
                            <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                            <div class="dropdown-menu dropdown-menu-right">
                                @if($l->status !== 'approved')
                                    @if(hasPermission("student_leave","approve"))<a class="dropdown-item text-success" href="{{ route('vendor.school.student-leave.approve', $l->id) }}"
                                       onclick="return confirm('Approve this leave? Attendance will be marked as Leave for those dates.')"><i class="tio-checkmark-circle"></i> Approve</a>@endif
                                @endif
                                @if($l->status === 'pending')
                                    @if(hasPermission("student_leave","reject"))<a class="dropdown-item text-warning" href="javascript:;" onclick="rejectLeave({{ $l->id }})"><i class="tio-clear-circle"></i> Reject</a>@endif
                                @endif
                                @if(hasPermission("student_leave","delete"))<a class="dropdown-item text-danger" href="{{ route('vendor.school.student-leave.delete', $l->id) }}" onclick="return confirm('Delete this leave request?')"><i class="tio-delete"></i> Delete</a>@endif
                            </div>
                        </div>
                        <form id="reject-{{ $l->id }}" action="{{ route('vendor.school.student-leave.reject', $l->id) }}" method="POST" class="d-none">
                            @csrf<input type="hidden" name="remarks" class="rj-remarks">
                        </form>
                    </td>
                </tr>
            @empty<tr><td colspan="8" class="text-center text-muted py-5">No leave requests yet.</td></tr>@endforelse
            </tbody>
        </table>
    </div></div></div>@endif
    @if(hasPermission("student_leave","view") && count($leaves))<div class="mt-3 px-2">{!! $leaves->links() !!}</div>@endif
</div>
@endsection

@push('script_2')
<script>
function rejectLeave(id) {
    var r = prompt('Reason for rejection (optional):', '');
    if (r === null) return;
    var f = document.getElementById('reject-' + id);
    f.querySelector('.rj-remarks').value = r;
    f.submit();
}
</script>
@endpush
