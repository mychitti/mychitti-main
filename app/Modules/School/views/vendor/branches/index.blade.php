@extends('layouts.vendor.app')
@section('title', 'Branches')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-city mr-1"></i> Branches</h1>
        <a href="{{ route('vendor.school.dashboard') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    <div class="alert alert-soft-info" style="background:#eef2ff;border:none;color:#3730a3;">
        <i class="tio-info-outined mr-1"></i> A branch is a campus of your school. New students, fees, attendance, admissions &amp; certificates
        are recorded against the <b>active branch</b> (chosen from the sidebar). The shared academic setup (classes, subjects, sessions) applies to all branches.
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card mb-3">
                <form action="{{ route('vendor.school.branches.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <input type="hidden" name="id" id="b_id">
                        <div class="form-group"><label class="input-label">Branch Name *</label>
                            <input name="name" id="b_name" class="form-control" maxlength="190" placeholder="e.g. City Campus" required></div>
                        <div class="form-group mb-0"><label class="input-label">Address</label>
                            <input name="address" id="b_address" class="form-control" maxlength="255"></div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="button" class="btn btn-outline-secondary" onclick="resetBranch()">Clear</button>
                        <button class="btn btn--primary"><i class="tio-save"></i> Save Branch</button>
                    </div>
                </form>
            </div></div>
        </div>

        <div class="col-lg-7">
            <div class="card"><div class="card-body p-0"><div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                    <thead class="thead-light"><tr><th>Branch</th><th>Address</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                    @forelse($branches as $b)
                        <tr>
                            <td class="font-weight-bold">{{ $b->name }}
                                @if((int) school_active_branch_id() === (int) $b->id)<span class="badge badge-soft-success ml-1">Active</span>@endif
                            </td>
                            <td>{{ $b->address ?? '—' }}</td>
                            <td class="text-right">
                                <div class="dropdown sch-actions">
                                    <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="{{ route('vendor.school.branches.switch', $b->id) }}"><i class="tio-checkmark-circle"></i> Use This Branch</a>
                                        <button type="button" class="dropdown-item" onclick='editBranch(@json($b))'><i class="tio-edit"></i> Edit</button>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger" href="{{ route('vendor.school.branches.delete', $b->id) }}" onclick="return confirm('Delete this branch?')"><i class="tio-delete"></i> Delete</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty<tr><td colspan="3" class="text-center text-muted py-5">No branches yet. Add your first campus.</td></tr>@endforelse
                    </tbody>
                </table>
            </div></div></div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
function editBranch(b){
    document.getElementById('b_id').value = b.id;
    document.getElementById('b_name').value = b.name || '';
    document.getElementById('b_address').value = b.address || '';
    window.scrollTo({top:0, behavior:'smooth'});
}
function resetBranch(){
    document.getElementById('b_id').value = '';
    document.getElementById('b_name').value = '';
    document.getElementById('b_address').value = '';
}
</script>
@endpush
