@extends('layouts.vendor.app')
@section('title', 'Enquiry ' . $enquiry->enquiry_no)

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-user-add mr-1"></i> {{ $enquiry->enquiry_no }}
            <span class="badge {{ $enquiry->statusBadge() }} ml-2" style="font-size:12px;">{{ $enquiry->statusLabel() }}</span>
        </h1>
        <div>
            <a href="{{ route('vendor.school.admissions.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
            <a href="{{ route('vendor.school.admissions.edit', $enquiry->id) }}" class="btn btn-sm btn-outline-primary"><i class="tio-edit"></i> Edit</a>
            @if($enquiry->status === 'admitted' && $enquiry->converted_student_id)
                <a href="{{ route('vendor.school.students.show', $enquiry->converted_student_id) }}" class="btn btn-sm btn-outline-success"><i class="tio-user"></i> View Student</a>
            @else
                <a href="{{ route('vendor.school.admissions.convert', $enquiry->id) }}" class="btn btn-sm btn--primary" onclick="return confirm('Convert this enquiry into a student admission?')"><i class="tio-user-add"></i> Admit Student</a>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3"><div class="card-header py-3"><i class="tio-info-outined mr-1 text-primary"></i> Details</div><div class="card-body">
                <div class="row">
                    @php
                        $rows = [
                            ['Student Name', $enquiry->student_name],
                            ['Date of Birth', $enquiry->dob ? \Carbon\Carbon::parse($enquiry->dob)->format('d/m/Y') : '—'],
                            ['Gender', ucfirst($enquiry->gender ?: '—')],
                            ['Seeking Class', $enquiry->seekingClass?->name ?? '—'],
                            ['Previous School', $enquiry->previous_school ?: '—'],
                            ['Source', $enquiry->source ?: '—'],
                            ['Guardian', $enquiry->guardian_name ?: '—'],
                            ['Guardian Phone', $enquiry->guardian_phone ?: '—'],
                            ['Alt Phone', $enquiry->phone ?: '—'],
                            ['Email', $enquiry->email ?: '—'],
                            ['Enquiry Date', $enquiry->enquiry_date ? \Carbon\Carbon::parse($enquiry->enquiry_date)->format('d/m/Y') : '—'],
                            ['Follow-up Date', $enquiry->follow_up_date ? \Carbon\Carbon::parse($enquiry->follow_up_date)->format('d/m/Y') : '—'],
                        ];
                    @endphp
                    @foreach($rows as [$label, $val])
                        <div class="col-md-6 mb-3">
                            <div class="text-muted" style="font-size:12px;">{{ $label }}</div>
                            <div class="font-weight-bold">{{ $val }}</div>
                        </div>
                    @endforeach
                </div>
                @if($enquiry->remarks)
                    <div class="mt-2"><div class="text-muted" style="font-size:12px;">Remarks</div><div>{{ $enquiry->remarks }}</div></div>
                @endif
            </div></div>
        </div>

        <div class="col-lg-4">
            <div class="card"><div class="card-header py-3"><i class="tio-sync mr-1 text-primary"></i> Update Status</div><div class="card-body">
                <form action="{{ route('vendor.school.admissions.status', $enquiry->id) }}" method="POST">
                    @csrf
                    <div class="form-group"><label class="input-label">Status</label>
                        <select name="status" class="form-control js-select2-custom">
                            @foreach($statuses as $k => $v)<option value="{{ $k }}" @selected($enquiry->status===$k)>{{ $v }}</option>@endforeach
                        </select></div>
                    <div class="form-group"><label class="input-label">Follow-up Date</label>
                        <input type="date" name="follow_up_date" class="form-control" value="{{ $enquiry->follow_up_date?->format('Y-m-d') }}"></div>
                    <button class="btn btn--primary btn-block"><i class="tio-checkmark"></i> Save Status</button>
                </form>
                <hr>
                <a href="{{ route('vendor.school.admissions.delete', $enquiry->id) }}" class="btn btn-sm btn-outline-danger btn-block"
                   onclick="return confirm('Delete this enquiry?')"><i class="tio-delete"></i> Delete Enquiry</a>
            </div></div>
        </div>
    </div>
</div>
@endsection
