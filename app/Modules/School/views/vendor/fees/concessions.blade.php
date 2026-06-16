@extends('layouts.vendor.app')
@section('title', 'Scholarships & Concessions')

@php $cur = fn($v) => \App\CentralLogics\Helpers::format_currency($v); @endphp

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-gift mr-1"></i> Scholarships &amp; Concessions</h1>
        <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
            <a href="{{ route('vendor.school.fees.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back to Fees</a>
            <form method="GET">
                <select name="session" class="form-control form-control-sm" onchange="this.form.submit()" style="min-width:160px;">
                    @foreach($sessions as $s)<option value="{{ $s->id }}" @selected((string)$sessionId===(string)$s->id)>{{ $s->name }}</option>@endforeach
                </select>
            </form>
        </div>
    </div>

    {{-- Summary --}}
    <div class="row">
        <div class="col-sm-6 col-xl-4 mb-3"><div class="sch-stat sch-stat--green">
            <i class="tio-gift sch-stat-ico"></i><div class="sch-stat-label">Concession Awarded</div>
            <div class="sch-stat-value">{{ $cur($awarded) }}</div>
            <div class="sch-stat-sub">this session</div>
        </div></div>
        <div class="col-sm-6 col-xl-4 mb-3"><div class="sch-stat sch-stat--indigo">
            <i class="tio-user-add sch-stat-ico"></i><div class="sch-stat-label">Beneficiaries</div>
            <div class="sch-stat-value">{{ $beneficiaries }}</div>
            <div class="sch-stat-sub">students with active scholarship</div>
        </div></div>
        <div class="col-sm-6 col-xl-4 mb-3"><div class="sch-stat sch-stat--sky">
            <i class="tio-bookmarks sch-stat-ico"></i><div class="sch-stat-label">Active Schemes</div>
            <div class="sch-stat-value">{{ $schemes->where('is_active', true)->count() }}</div>
            <div class="sch-stat-sub">of {{ $schemes->count() }} total</div>
        </div></div>
    </div>

    <div class="row">
        {{-- Scheme form --}}
        <div class="col-lg-5 mb-3">
            <div class="card"><div class="card-header py-3"><h6 class="mb-0">Add / Edit Scheme</h6></div>
            <form action="{{ route('vendor.school.fees.concessions.save') }}" method="POST">
                @csrf <input type="hidden" name="id" id="c_id">
                <div class="card-body">
                    <div class="form-group"><label class="input-label">Scheme Name *</label>
                        <input name="name" id="c_name" class="form-control" maxlength="150" placeholder="e.g. Merit Scholarship, Sibling Discount, Staff Ward, RTE" required></div>
                    <div class="form-row">
                        <div class="form-group col-6"><label class="input-label">Type *</label>
                            <select name="type" id="c_type" class="form-control" onchange="cTypeUI()">
                                <option value="percent">Percentage (%)</option>
                                <option value="fixed">Fixed Amount</option>
                            </select></div>
                        <div class="form-group col-6"><label class="input-label" id="c_value_lbl">Value (%) *</label>
                            <input type="number" step="0.01" min="0" name="value" id="c_value" class="form-control" required></div>
                    </div>
                    <div class="form-group" id="c_max_wrap"><label class="input-label">Max Cap (optional)</label>
                        <input type="number" step="0.01" min="0" name="max_amount" id="c_max" class="form-control" placeholder="upper limit for a percentage discount"></div>
                    <div class="form-group"><label class="input-label">Description</label>
                        <input name="description" id="c_desc" class="form-control" maxlength="255" placeholder="who it applies to / eligibility"></div>
                    <div class="form-row">
                        <div class="form-group col-6"><label class="input-label">Sort Order</label>
                            <input type="number" name="sort_order" id="c_sort" class="form-control" value="0"></div>
                        <div class="form-group col-6 d-flex align-items-end pb-2">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="c_active" name="is_active" value="1" checked>
                                <label class="custom-control-label" for="c_active">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="button" class="btn btn-white btn-sm" onclick="cReset()">Clear</button>
                    @if(hasPermission('scholarship','add'))<button class="btn btn--primary btn-sm"><i class="tio-save"></i> Save Scheme</button>@endif
                </div>
            </form></div>
        </div>

        {{-- Scheme list --}}
        <div class="col-lg-7 mb-3">
            <div class="card"><div class="card-header py-3"><h6 class="mb-0">Schemes</h6></div>
            @if(hasPermission("scholarship","view"))<div class="table-responsive"><table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                <thead class="thead-light"><tr><th>Scheme</th><th>Benefit</th><th>Status</th><th class="text-right">Action</th></tr></thead>
                <tbody>
                @forelse($schemes as $s)
                    <tr>
                        <td class="font-weight-bold">{{ $s->name }}@if($s->description)<br><small class="text-muted font-weight-normal">{{ $s->description }}</small>@endif</td>
                        <td><span class="badge badge-soft-info">{{ $s->label }}</span></td>
                        <td><span class="badge {{ $s->is_active ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ $s->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-right">
                            <button class="btn btn-sm btn-white" onclick='cEdit(@json($s))'><i class="tio-edit"></i></button>
                            @if(hasPermission('scholarship','delete'))<a href="{{ route('vendor.school.fees.concessions.delete', $s->id) }}" class="btn btn-sm btn-white text-danger" onclick="return confirm('Delete this scheme?')"><i class="tio-delete"></i></a>@endif
                        </td>
                    </tr>
                @empty<tr><td colspan="4" class="text-center text-muted py-4">No schemes yet. Create one to start awarding scholarships.</td></tr>@endforelse
                </tbody>
            </table></div>@endif</div>
        </div>
    </div>

    {{-- Assignments --}}
    <div class="card">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center" style="gap:10px;">
            <h6 class="mb-0">Student Assignments</h6>
            <form action="{{ route('vendor.school.fees.concessions.assign') }}" method="POST" class="d-flex flex-wrap align-items-center" style="gap:8px;">
                @csrf
                <input type="hidden" name="session_id" value="{{ $sessionId }}">
                <input name="admission_no" class="form-control form-control-sm" style="width:140px;" placeholder="Admission No" required>
                <select name="fee_concession_id" class="form-control form-control-sm" style="width:210px;" required>
                    <option value="">— Select Scheme —</option>
                    @foreach($schemes->where('is_active', true) as $s)<option value="{{ $s->id }}">{{ $s->name }} ({{ $s->label }})</option>@endforeach
                </select>
                <input name="note" class="form-control form-control-sm" style="width:150px;" placeholder="Note (optional)">
                @if(hasPermission('scholarship','add'))<button class="btn btn-sm btn--primary"><i class="tio-add"></i> Assign</button>@endif
            </form>
        </div>
        @if(hasPermission("scholarship","view"))<div class="table-responsive"><table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
            <thead class="thead-light"><tr><th>Student</th><th>Scheme</th><th>Benefit</th><th>Note</th><th class="text-right">Action</th></tr></thead>
            <tbody>
            @forelse($assignments as $a)
                <tr>
                    <td class="font-weight-bold">{{ $a->student?->name ?? '—' }}<br><small class="text-muted">{{ $a->student?->admission_no }}</small></td>
                    <td>{{ $a->scheme?->name ?? '—' }}</td>
                    <td><span class="badge badge-soft-info">{{ $a->scheme?->label ?? '—' }}</span></td>
                    <td><small class="text-muted">{{ $a->note }}</small></td>
                    <td class="text-right">
                        @if(hasPermission('scholarship','delete'))<a href="{{ route('vendor.school.fees.concessions.assign.delete', $a->id) }}" class="btn btn-sm btn-white text-danger" onclick="return confirm('Remove this assignment?')"><i class="tio-delete"></i></a>@endif
                    </td>
                </tr>
            @empty<tr><td colspan="5" class="text-center text-muted py-4">No scholarships assigned yet. Assign a scheme to a student above — it auto-applies at fee collection.</td></tr>@endforelse
            </tbody>
        </table></div>@endif
    </div>
</div>
@endsection

@push('script_2')
<script>
function cTypeUI() {
    var t = document.getElementById('c_type').value;
    document.getElementById('c_value_lbl').textContent = t === 'percent' ? 'Value (%) *' : 'Value (amount) *';
    document.getElementById('c_max_wrap').style.display = t === 'percent' ? 'block' : 'none';
}
function cEdit(s) {
    document.getElementById('c_id').value = s.id;
    document.getElementById('c_name').value = s.name;
    document.getElementById('c_type').value = s.type;
    document.getElementById('c_value').value = s.value;
    document.getElementById('c_max').value = s.max_amount ?? '';
    document.getElementById('c_desc').value = s.description ?? '';
    document.getElementById('c_sort').value = s.sort_order;
    document.getElementById('c_active').checked = !!s.is_active;
    cTypeUI();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
function cReset() {
    ['c_id', 'c_name', 'c_value', 'c_max', 'c_desc'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('c_type').value = 'percent';
    document.getElementById('c_sort').value = 0;
    document.getElementById('c_active').checked = true;
    cTypeUI();
}
cTypeUI();
</script>
@endpush
