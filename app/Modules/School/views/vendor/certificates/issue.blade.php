@extends('layouts.vendor.app')
@section('title', 'Issue Certificate')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-add-circle-outlined mr-1"></i> Issue Certificate</h1>
        <a href="{{ route('vendor.school.certificates.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    <div class="row justify-content-center"><div class="col-lg-7">
        <form action="{{ route('vendor.school.certificates.store') }}" method="POST">
            @csrf
            <div class="card"><div class="card-body">
                <div class="form-group">
                    <label class="input-label">Student *</label>
                    <select name="student_id" id="studentSelect" class="form-control" style="width:100%;" required>
                        <option value="">— Select student —</option>
                        @if($preStudent)
                            @php
                                $preLabel = $preStudent->name;
                                if ($preStudent->admission_no) { $preLabel .= ' (' . $preStudent->admission_no . ')'; }
                                $preRoll = optional($preStudent->currentEnrollment)->roll_no;
                                if ($preRoll) { $preLabel .= ' · Roll ' . $preRoll; }
                            @endphp
                            <option value="{{ $preStudent->id }}" selected>{{ $preLabel }}</option>
                        @endif
                    </select>
                    @error('student_id')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                <div class="form-group">
                    <label class="input-label">Certificate Type *</label>
                    <select name="type" class="form-control js-select2-custom" id="certType" required>
                        @foreach($types as $k => $label)
                            <option value="{{ $k }}" @selected($selectedType === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="input-label">Design Template</label>
                    <select name="design" class="form-control js-select2-custom">
                        @foreach($designs as $k => $label)
                            <option value="{{ $k }}" @selected(old('design', $defaultDesign) === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Defaults to your store template (set under <a href="{{ route('vendor.school.certificates.settings') }}">Templates</a>).</small>
                </div>

                <div class="form-group" id="reasonWrap">
                    <label class="input-label">Purpose / Reason</label>
                    <input name="reason" class="form-control" value="{{ old('reason') }}" maxlength="255"
                           placeholder="e.g. bank account opening, scholarship application">
                    <small class="text-muted">Used by the {reason} token (mainly for Bonafide certificates).</small>
                </div>

                <div class="form-group mb-0">
                    <label class="input-label">Issue Date</label>
                    <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date', now()->toDateString()) }}">
                </div>
            </div>
            <div class="card-footer text-right">
                <button class="btn btn--primary"><i class="tio-checkmark"></i> Generate Certificate</button>
            </div></div>
        </form>
    </div></div>
</div>
@endsection

@push('script_2')
<script>
(function () {
    const type = document.getElementById('certType');
    const reasonWrap = document.getElementById('reasonWrap');
    function toggle() { reasonWrap.style.display = type.value === 'bonafide' ? '' : 'none'; }
    type.addEventListener('change', toggle); toggle();
})();

$(function () {
    $('#studentSelect').select2({
        placeholder: 'Search by name, roll no or admission no…',
        minimumInputLength: 1,
        width: '100%',
        ajax: {
            url: "{{ route('vendor.school.certificates.students.search') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) {
                return {
                    results: data.map(function (s) {
                        var t = s.name;
                        if (s.admission_no) t += ' (' + s.admission_no + ')';
                        if (s.roll_no) t += ' · Roll ' + s.roll_no;
                        return { id: s.id, text: t };
                    })
                };
            },
            cache: true
        }
    });
});
</script>
@endpush
