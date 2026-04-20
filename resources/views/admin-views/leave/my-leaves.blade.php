@extends('layouts.admin.app')

@section('title', 'My Leaves')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title"><i class="tio-calendar"></i> My Leaves</h1>
    </div>

    {{-- Balance Cards --}}
    <div class="row mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1">CL Balance</h6>
                    <h2 class="mb-0 {{ $clBalance < 0 ? 'text-danger' : 'text-success' }}">{{ $clBalance }}</h2>
                    <small class="text-muted">{{ $clTaken }} taken this month</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1">SL Balance</h6>
                    <h2 class="mb-0 {{ $slBalance < 0 ? 'text-danger' : 'text-success' }}">{{ $slBalance }}</h2>
                    <small class="text-muted">{{ $slTaken }} taken this month</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Request Form --}}
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Request Leave</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.leave.request') }}">
                        @csrf
                        <div class="form-group">
                            <label class="input-label">Leave Date <span class="text-danger">*</span></label>
                            <input type="date" name="leave_date" class="form-control @error('leave_date') is-invalid @enderror"
                                value="{{ old('leave_date') }}" min="{{ date('Y-m-d') }}" required>
                            @error('leave_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="input-label">Leave Type <span class="text-danger">*</span></label>
                            <select name="leave_type" class="form-control @error('leave_type') is-invalid @enderror" required>
                                <option value="">-- Select --</option>
                                <option value="CL" {{ old('leave_type') == 'CL' ? 'selected' : '' }}>CL – Casual Leave</option>
                                <option value="SL" {{ old('leave_type') == 'SL' ? 'selected' : '' }}>SL – Sick Leave</option>
                                <option value="HDS" {{ old('leave_type') == 'HDS' ? 'selected' : '' }}>Half Day (First Half)</option>
                                <option value="HDF" {{ old('leave_type') == 'HDF' ? 'selected' : '' }}>Half Day (Second Half)</option>
                            </select>
                            @error('leave_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="input-label">Reason <span class="text-danger">*</span></label>
                            <textarea name="reason" rows="3" maxlength="500"
                                class="form-control @error('reason') is-invalid @enderror"
                                required>{{ old('reason') }}</textarea>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn--primary btn-block">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Leave History --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">My Leave History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Applied On</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($myLeaves as $leave)
                                    <tr>
                                        <td>{{ $loop->iteration + ($myLeaves->firstItem() - 1) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($leave->leave_date)->format('d M Y') }}</td>
                                        <td>
                                            @php
                                                $typeLabels = ['CL'=>'CL','SL'=>'SL','HDS'=>'Half Day (1st)','HDF'=>'Half Day (2nd)'];
                                            @endphp
                                            <span class="badge badge-soft-info">{{ $typeLabels[$leave->leave_type] ?? $leave->leave_type }}</span>
                                        </td>
                                        <td>{{ Str::limit($leave->reason, 60) }}</td>
                                        <td>
                                            @if($leave->status === 'approved')
                                                <span class="badge badge-soft-success">Approved</span>
                                            @elseif($leave->status === 'rejected')
                                                <span class="badge badge-soft-danger">Rejected</span>
                                            @else
                                                <span class="badge badge-soft-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($leave->created_at)->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No leave records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($myLeaves->hasPages())
                    <div class="card-footer">
                        {{ $myLeaves->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
