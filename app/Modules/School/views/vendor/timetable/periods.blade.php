@extends('layouts.vendor.app')
@section('title', 'Timetable Periods')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-time mr-1"></i> Periods / Time Slots</h1>
        <a href="{{ route('vendor.school.timetable.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card mb-3"><div class="card-body">
                <form action="{{ route('vendor.school.timetable.periods.save') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="p_id">
                    <div class="form-row">
                        <div class="form-group col-4"><label class="input-label">Order #</label>
                            <input type="number" name="period_no" id="p_no" class="form-control" min="0" value="0"></div>
                        <div class="form-group col-8"><label class="input-label">Name *</label>
                            <input name="name" id="p_name" class="form-control" maxlength="100" placeholder="Period 1" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-6"><label class="input-label">Start</label>
                            <input type="time" name="start_time" id="p_start" class="form-control"></div>
                        <div class="form-group col-6"><label class="input-label">End</label>
                            <input type="time" name="end_time" id="p_end" class="form-control"></div>
                    </div>
                    <div class="form-group">
                        <label class="d-flex align-items-center mb-0" style="cursor:pointer;">
                            <input type="checkbox" name="is_break" id="p_break" value="1" class="mr-2"> This is a break (recess / lunch)
                        </label>
                    </div>
                    @if(hasPermission("timetable","edit"))<button class="btn btn--primary"><i class="tio-save"></i> Save Period</button>@endif
                    <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">Clear</button>
                </form>
            </div></div>
        </div>

        <div class="col-lg-7">
            <div class="card"><div class="card-body p-0"><div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                    <thead class="thead-light"><tr><th>#</th><th>Name</th><th>Time</th><th>Type</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                    @forelse($periods as $p)
                        <tr>
                            <td>{{ $p->period_no }}</td>
                            <td class="font-weight-bold">{{ $p->name }}</td>
                            <td>@if($p->start_time && $p->end_time){{ \Carbon\Carbon::parse($p->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($p->end_time)->format('g:i A') }}@else—@endif</td>
                            <td>@if($p->is_break)<span class="badge badge-soft-warning">Break</span>@else<span class="badge badge-soft-info">Class</span>@endif</td>
                            <td class="text-right">
                                <div class="dropdown sch-actions">
                                    <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <button type="button" class="dropdown-item" onclick='editP(@json($p))'><i class="tio-edit"></i> Edit</button>
                                        @if(hasPermission("timetable","edit"))<a class="dropdown-item text-danger" href="{{ route('vendor.school.timetable.periods.delete', $p->id) }}" onclick="return confirm('Delete this period?')"><i class="tio-delete"></i> Delete</a>@endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty<tr><td colspan="5" class="text-center text-muted py-5">No periods yet. Add your first slot.</td></tr>@endforelse
                    </tbody>
                </table>
            </div></div></div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
function editP(p){
    document.getElementById('p_id').value = p.id;
    document.getElementById('p_no').value = p.period_no;
    document.getElementById('p_name').value = p.name;
    document.getElementById('p_start').value = p.start_time ? p.start_time.substring(0,5) : '';
    document.getElementById('p_end').value = p.end_time ? p.end_time.substring(0,5) : '';
    document.getElementById('p_break').checked = !!p.is_break;
    window.scrollTo({top:0, behavior:'smooth'});
}
function resetForm(){
    document.getElementById('p_id').value = '';
    document.getElementById('p_no').value = 0;
    document.getElementById('p_name').value = '';
    document.getElementById('p_start').value = '';
    document.getElementById('p_end').value = '';
    document.getElementById('p_break').checked = false;
}
</script>
@endpush
