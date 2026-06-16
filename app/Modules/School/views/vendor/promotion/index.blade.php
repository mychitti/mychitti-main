@extends('layouts.vendor.app')
@section('title', 'Student Promotion')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header">
        <h1 class="page-header-title mb-0"><i class="tio-sort-ascending mr-1"></i> Student Promotion / Year-end Roll-over</h1>
    </div>

    {{-- Source selection --}}
    <div class="card mb-3"><div class="card-header py-3"><i class="tio-folder-opened mr-1 text-primary"></i> Select the class to roll over</div>
        <div class="card-body py-3">
            <form method="GET" class="form-row align-items-end" id="srcForm">
                <div class="col-md-4"><label class="input-label mb-1">From Session</label>
                    <select name="src_session" class="form-control form-control-sm js-select2-custom" onchange="this.form.submit()">
                        @foreach($sessions as $s)<option value="{{ $s->id }}" @selected((string)$srcSession===(string)$s->id)>{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="input-label mb-1">Class</label>
                    <select name="src_class" class="form-control form-control-sm js-select2-custom"
                            onchange="document.getElementById('srcSection').value=''; this.form.submit()">
                        <option value="">Select class</option>
                        @foreach($classes as $c)<option value="{{ $c->id }}" @selected((string)$srcClass===(string)$c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="input-label mb-1">Section</label>
                    <select name="src_section" id="srcSection" class="form-control form-control-sm js-select2-custom" onchange="this.form.submit()">
                        <option value="">All sections</option>
                        @foreach($sections as $sec)<option value="{{ $sec->id }}" @selected((string)$srcSection===(string)$sec->id)>{{ $sec->schoolClass?->name }} - {{ $sec->name }}</option>@endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($srcClass)
        @if($roster->isEmpty())
            <div class="card"><div class="card-body text-center text-muted py-5">No active students found for this class/section in the selected session.</div></div>
        @else
        <form action="{{ route('vendor.school.promotion.process') }}" method="POST">
            @csrf
            <input type="hidden" name="action" id="promoAction">
            <input type="hidden" name="src_session" value="{{ $srcSession }}">
            <input type="hidden" name="src_class" value="{{ $srcClass }}">

            {{-- Target --}}
            <div class="card mb-3"><div class="card-header py-3"><i class="tio-sort-ascending mr-1 text-primary"></i> Promote to</div>
                <div class="card-body py-3">
                    @if($isFinalClass)
                        <div class="alert alert-soft-warning mb-3" style="background:#fef3c7;border:none;color:#92400e;">
                            <i class="tio-info-outined mr-1"></i> This is the final class (no higher class defined). Use <b>Graduate</b> to move these students to alumni.
                        </div>
                    @endif
                    <div class="form-row align-items-end">
                        <div class="col-md-4"><label class="input-label mb-1">To Session *</label>
                            <select name="target_session" class="form-control form-control-sm js-select2-custom">
                                @foreach($sessions as $s)<option value="{{ $s->id }}" @selected($s->is_current)>{{ $s->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-4"><label class="input-label mb-1">To Class *</label>
                            <select name="target_class" class="form-control form-control-sm js-select2-custom">
                                <option value="">Select class</option>
                                @foreach($classes as $c)<option value="{{ $c->id }}" @selected($nextClass && $nextClass->id===$c->id)>{{ $c->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-4"><label class="input-label mb-1">To Section</label>
                            <select name="target_section" class="form-control form-control-sm js-select2-custom">
                                <option value="">— (assign later)</option>
                                @foreach($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->schoolClass?->name }} - {{ $sec->name }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Roster --}}
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Students ({{ $roster->count() }})</h6>
                    <div>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="toggleAll(true)">Select All</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="toggleAll(false)">None</button>
                    </div>
                </div>
                <div class="card-body p-0"><div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                        <thead class="thead-light"><tr>
                            <th style="width:40px;"><input type="checkbox" id="chkAll" checked onclick="toggleAll(this.checked)"></th>
                            <th>Roll</th><th>Admission No</th><th>Student</th><th>Current Section</th>
                        </tr></thead>
                        <tbody>
                        @foreach($roster as $e)
                            <tr>
                                <td><input type="checkbox" name="student_ids[]" value="{{ $e->student_id }}" class="promo-chk" checked></td>
                                <td>{{ $e->roll_no ?? '—' }}</td>
                                <td>{{ $e->student->admission_no }}</td>
                                <td class="font-weight-bold">{{ $e->student->name }}</td>
                                <td>{{ $e->section?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div></div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <span class="text-muted" style="font-size:12px;">Unselected students are left unchanged in their current class.</span>
                    <div>
                        @if(hasPermission('student_promotion','promote'))
                            @if($isFinalClass)
                                <button type="submit" class="btn btn-outline-danger" onclick="document.getElementById('promoAction').value='graduate'; return confirm('Graduate the selected students to alumni? Their current enrolment will be closed.');">
                                    <i class="tio-user-shield"></i> Graduate Selected
                                </button>
                            @endif
                            <button type="submit" class="btn btn--primary" onclick="document.getElementById('promoAction').value='promote'; return confirm('Promote the selected students to the chosen class?');">
                                <i class="tio-sort-ascending"></i> Promote Selected
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </form>
        @endif
    @else
        <div class="card"><div class="card-body text-center text-muted py-5">Select a session &amp; class above to load students for roll-over.</div></div>
    @endif
</div>
@endsection

@push('script_2')
<script>
function toggleAll(state){
    document.querySelectorAll('.promo-chk').forEach(c => c.checked = state);
    var all = document.getElementById('chkAll'); if (all) all.checked = state;
}
</script>
@endpush
