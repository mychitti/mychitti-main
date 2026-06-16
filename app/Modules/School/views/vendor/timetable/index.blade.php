@extends('layouts.vendor.app')
@section('title', 'Timetable')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-table mr-1"></i> Timetable</h1>
        <div>
            <a href="{{ route('vendor.school.timetable.teacher') }}" class="btn btn-sm btn-outline-info"><i class="tio-user"></i> Teacher View</a>
            <a href="{{ route('vendor.school.timetable.substitutions') }}" class="btn btn-sm btn-outline-warning"><i class="tio-replace"></i> Substitutions</a>
            <a href="{{ route('vendor.school.timetable.periods') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-time"></i> Periods</a>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body py-3">
        <form method="GET" class="form-row align-items-end" id="ttFilter">
            <div class="col-md-6"><label class="input-label mb-1">Class</label>
                <select name="class_id" id="ttClass" class="form-control form-control-sm" required>
                    <option value="">Select class</option>
                    @foreach($classes as $c)<option value="{{ $c->id }}" @selected((string)$classId===(string)$c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-6"><label class="input-label mb-1">Section</label>
                <select name="section_id" id="ttSection" class="form-control form-control-sm" required>
                    <option value="">Select section</option>
                    @foreach($sections as $s)<option value="{{ $s->id }}" data-class="{{ $s->school_class_id }}" @selected((string)$sectionId===(string)$s->id)>{{ $s->schoolClass?->name }} - {{ $s->name }}</option>@endforeach
                </select>
            </div>
        </form>
    </div></div>

    @if(!count($periods))
        <div class="alert alert-warning">No periods defined yet. <a href="{{ route('vendor.school.timetable.periods') }}">Set up periods</a> first.</div>
    @elseif($classId && $sectionId)
        <form action="{{ route('vendor.school.timetable.save') }}" method="POST">
            @csrf
            <input type="hidden" name="class_id" value="{{ $classId }}">
            <input type="hidden" name="section_id" value="{{ $sectionId }}">
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Weekly Grid</h6>
                    <div>
                        <a href="{{ route('vendor.school.timetable.show', ['section_id' => $sectionId]) }}" class="btn btn-xs btn-outline-primary" target="_blank"><i class="tio-print"></i> Print</a>
                        <a href="{{ route('vendor.school.timetable.pdf', ['section_id' => $sectionId]) }}" class="btn btn-xs btn-outline-success" target="_blank"><i class="tio-download"></i> PDF</a>
                    </div>
                </div>
                <div class="card-body p-0"><div class="table-responsive">
                    <table class="table table-bordered table-align-middle mb-0" style="font-size:12px;">
                        <thead class="thead-light">
                            <tr>
                                <th style="min-width:120px;">Period</th>
                                @foreach($days as $dn => $day)<th class="text-center" style="min-width:150px;">{{ $day }}</th>@endforeach
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($periods as $p)
                            <tr class="{{ $p->is_break ? 'bg-light' : '' }}">
                                <td class="font-weight-bold">
                                    {{ $p->name }}
                                    @if($p->start_time && $p->end_time)<br><small class="text-muted">{{ \Carbon\Carbon::parse($p->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($p->end_time)->format('g:i A') }}</small>@endif
                                </td>
                                @foreach($days as $dn => $day)
                                    @php $cell = $grid[$dn][$p->id] ?? null; @endphp
                                    @if($p->is_break)
                                        <td class="text-center text-muted">— Break —</td>
                                    @else
                                        <td>
                                            <select name="entry[{{ $dn }}][{{ $p->id }}][subject]" class="form-control form-control-sm tt-subject mb-1" data-day="{{ $dn }}" data-period="{{ $p->id }}">
                                                <option value="">—</option>
                                                @foreach($subjects as $sub)<option value="{{ $sub->id }}" @selected(optional($cell)->subject_id==$sub->id)>{{ $sub->name }}</option>@endforeach
                                            </select>
                                            <select name="entry[{{ $dn }}][{{ $p->id }}][teacher]" class="form-control form-control-sm tt-teacher" data-day="{{ $dn }}" data-period="{{ $p->id }}">
                                                <option value="">— Teacher —</option>
                                                @foreach($teachers as $t)<option value="{{ $t->id }}" @selected(optional($cell)->teacher_emp_id==$t->id)>{{ trim(($t->f_name ?? '').' '.($t->l_name ?? '')) }}</option>@endforeach
                                            </select>
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div></div>
                <div class="card-footer text-right">@if(hasPermission("timetable","add"))<button class="btn btn--primary"><i class="tio-save"></i> Save Timetable</button>@endif</div>
            </div>
        </form>
    @else
        <div class="card"><div class="card-body text-center text-muted py-5">Select a class &amp; section to build its timetable.</div></div>
    @endif
</div>
@endsection

@push('script_2')
<script>
    (function () {
        var classSel = document.getElementById('ttClass');
        var sectionSel = document.getElementById('ttSection');
        if (classSel && sectionSel) {
            function filterSections() {
                var cid = classSel.value;
                Array.prototype.forEach.call(sectionSel.options, function (o) {
                    if (!o.value) return;
                    var match = o.getAttribute('data-class') === cid;
                    o.hidden = !match;
                    o.disabled = !match;
                });
                var cur = sectionSel.selectedOptions[0];
                if (cur && cur.value && cur.getAttribute('data-class') !== cid) sectionSel.value = '';
            }
            classSel.addEventListener('change', function () {
                filterSections();
                sectionSel.focus();
            });
            sectionSel.addEventListener('change', function () {
                if (this.value) document.getElementById('ttFilter').submit();
            });
            filterSections();
        }
    })();

    var ttAutoMap = @json($autoMap);
    document.querySelectorAll('.tt-subject').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var teacher = ttAutoMap[sel.value];
            if (!teacher) return;
            var t = document.querySelector('.tt-teacher[data-day="' + sel.dataset.day + '"][data-period="' + sel.dataset.period + '"]');
            if (t && !t.value) t.value = teacher;
        });
    });
</script>
@endpush
