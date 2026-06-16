@extends('layouts.vendor.app')
@section('title', $student->name)

@section('content')
    <div class="content container-fluid school-page">
        @include('school::vendor.partials.theme')
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-header-title mb-0">{{ $student->name }}
                <span class="badge badge-soft-info">{{ $student->admission_no }}</span>
            </h1>
            <div class="d-flex" style="gap:8px;">
                <a href="{{ route('vendor.school.students.dashboard', $student->id) }}"
                    class="btn btn-sm btn-outline-primary"><i class="tio-chart-bar-1"></i> Dashboard</a>
                <a href="{{ route('vendor.school.students.id-card', $student->id) }}" target="_blank"
                    class="btn btn-sm btn-outline-info"><i class="tio-credit-card"></i> ID Card</a>
                @if(hasPermission("students","edit"))<a href="{{ route('vendor.school.students.edit', $student->id) }}" class="btn btn-sm btn--primary"><i
                        class="tio-edit"></i> Edit</a>@endif
                <a href="{{ route('vendor.school.students.index') }}" class="btn btn-sm btn-outline-secondary"><i
                        class="tio-back-ui"></i> Back</a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 text-center mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        @if ($student->photo)
                            <img src="{{ asset('storage/app/public/school/students/' . $student->photo) }}"
                                class="img-fluid rounded mb-2 mx-auto" style="max-height:160px;">
                        @else
                            <div class="avatar avatar-xl avatar-circle bg-soft-secondary mx-auto"><span
                                    class="avatar-initials">{{ strtoupper(substr($student->name, 0, 1)) }}</span></div>
                        @endif
                        <h6 class="mt-2 mb-0">{{ $student->name }}</h6>
                        <small class="text-muted">{{ $student->currentEnrollment?->schoolClass?->name }}
                            {{ $student->currentEnrollment?->section ? '- ' . $student->currentEnrollment->section->name : '' }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-9 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        @php
                            $address = trim(($student->address ?? '') . ' ' . ($student->city ?? '') . ' ' . ($student->state ?? '') . ' ' . ($student->pincode ?? ''));
                            $rows = [
                                'Admission No'   => $student->admission_no,
                                'Admission Date' => $student->admission_date?->format('d M Y'),
                                'Roll No'        => $student->currentEnrollment?->roll_no,
                                'Session'        => $student->currentEnrollment?->session?->name,
                                'DOB'            => $student->dob?->format('d M Y'),
                                'Gender'         => ucfirst($student->gender ?? ''),
                                'Blood Group'    => $student->blood_group,
                                'Category'       => $student->category,
                                'Phone'          => $student->phone,
                                'Email'          => $student->email,
                                'Guardian'       => trim(($student->guardian_name ?? '') . ' (' . ($student->guardian_relation ?? '') . ')', ' ()'),
                                'Guardian Phone' => $student->guardian_phone,
                            ];
                        @endphp
                        <div class="row sch-detail" style="font-size:14px;">
                            @foreach ($rows as $label => $val)
                                <div class="col-md-6 mb-2 d-flex">
                                    <span class="sch-dlabel text-muted">{{ $label }}</span>
                                    <span class="sch-dval font-weight-bold text-break">{{ $val ?: '—' }}</span>
                                </div>
                            @endforeach
                            <div class="col-12 mb-0 d-flex">
                                <span class="sch-dlabel text-muted">Address</span>
                                <span class="sch-dval font-weight-bold text-break">{{ $address ?: '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Documents ===== --}}
        <div class="card mt-3">
            <div class="card-header py-3">
                <i class="tio-folder mr-1 text-primary"></i> Documents
                <span class="badge badge-soft-secondary ml-1">{{ count($documents) }}</span>
            </div>

            {{-- inline upload bar --}}
            <div class="card-body border-bottom">
                <form action="{{ route('vendor.school.students.documents.store', $student->id) }}" method="POST"
                    enctype="multipart/form-data" class="form-row align-items-end">
                    @csrf
                    <div class="form-group col-md-3 mb-2 mb-md-0"><label class="input-label mb-1">Document Type *</label>
                        <select name="doc_type" class="form-control form-control-sm js-select2-custom" required>
                            @foreach ($docTypes as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4 mb-2 mb-md-0"><label class="input-label mb-1">Title / Note</label>
                        <input name="title" class="form-control form-control-sm" maxlength="190" placeholder="e.g. TC from ABC School (Class 4)">
                    </div>
                    <div class="form-group col-md-3 mb-2 mb-md-0"><label class="input-label mb-1">File *</label>
                        <input type="file" name="file" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                    </div>
                    <div class="form-group col-md-2 mb-0">
                        @if(hasPermission("students","edit"))<button class="btn btn-sm btn--primary btn-block"><i class="tio-upload"></i> Upload</button>@endif
                    </div>
                </form>
                @error('file')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                <small class="text-muted d-block mt-1">PDF or image, max 8 MB.</small>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                        <thead class="thead-light"><tr>
                            <th>Type</th><th>Title</th><th>Uploaded</th><th class="text-right">Action</th>
                        </tr></thead>
                        <tbody>
                            @forelse($documents as $d)
                                <tr>
                                    <td><span class="badge badge-soft-info">{{ $d->typeLabel() }}</span></td>
                                    <td>{{ $d->title }}</td>
                                    <td><small class="text-muted">{{ $d->created_at?->format('d M Y') }} · {{ $d->uploaded_by }}</small></td>
                                    <td class="text-right">
                                        <div class="dropdown sch-actions">
                                            <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item" href="{{ asset('storage/app/public/school/student-docs/' . $d->file) }}" target="_blank"><i class="tio-visible"></i> View</a>
                                                @if(hasPermission("students","edit"))<a class="dropdown-item text-danger" href="{{ route('vendor.school.students.documents.delete', [$student->id, $d->id]) }}" onclick="return confirm('Delete this document?')"><i class="tio-delete"></i> Delete</a>@endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty<tr><td colspan="4" class="text-center text-muted py-4">No documents uploaded yet.</td></tr>@endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
