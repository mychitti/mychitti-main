@extends('layouts.vendor.app')
@section('title', translate('Evaluate Homework'))

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
 
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <i class="tio-receipt mr-1"></i>
            {{ translate('Evaluate Homework') }}
        </h1>
        <a href="{{ route('vendor.school.homework.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-back-ui"></i> {{ translate('Back to List') }}
        </a>
    </div>

    <!-- Homework Summary Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h3 class="text-primary mb-2">{{ $homework->title }}</h3>
                    @if($homework->description)
                        <div class="p-3 bg-light rounded text-muted mb-3" style="white-space: pre-wrap; font-size: 0.9rem;">
                            {{ $homework->description }}
                        </div>
                    @endif
                    
                    <div class="d-flex flex-wrap align-items-center">
                        <div class="mr-4 mb-2">
                            <span class="text-muted mr-1">{{ translate('Class') }}:</span>
                            <span class="font-weight-bold badge badge-soft-info">{{ $homework->schoolClass?->name }}</span>
                        </div>
                        @if($homework->classSection)
                            <div class="mr-4 mb-2">
                                <span class="text-muted mr-1">{{ translate('Section') }}:</span>
                                <span class="font-weight-bold badge badge-soft-info">{{ $homework->classSection->name }}</span>
                            </div>
                        @endif
                        <div class="mr-4 mb-2">
                            <span class="text-muted mr-1">{{ translate('Subject') }}:</span>
                            <span class="font-weight-bold text-dark">{{ $homework->subject?->name }}</span>
                        </div>
                        @if($homework->max_marks)
                            <div class="mr-4 mb-2">
                                <span class="text-muted mr-1">{{ translate('Max Marks') }}:</span>
                                <span class="font-weight-bold text-danger">{{ number_format($homework->max_marks, 2) }}</span>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="col-md-4 border-left-md text-md-right mt-3 mt-md-0">
                    <div class="mb-2">
                        <span class="text-muted mr-1">{{ translate('Assigned') }}:</span>
                        <span class="font-weight-bold text-dark">{{ $homework->assign_date ? $homework->assign_date->format('d M Y') : '—' }}</span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted mr-1">{{ translate('Due Date') }}:</span>
                        <span class="font-weight-bold text-danger">{{ $homework->submission_date ? $homework->submission_date->format('d M Y') : '—' }}</span>
                    </div>
                    
                    @if($homework->attachment)
                        <a href="{{ asset('storage/app/public/school/homework/' . $homework->attachment) }}" target="_blank" class="btn btn-xs btn-outline-info">
                            <i class="tio-download-to mr-1"></i> {{ translate('Download Instructions') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Student Roster & Grading Form -->
    <form action="{{ route('vendor.school.homework.evaluate', $homework->id) }}" method="POST">
        @csrf
        <div class="card">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-header-title mb-0">
                    <i class="tio-users-class-outline mr-1"></i>
                    {{ translate('Student Submissions') }} ({{ count($roster) }})
                </h5>
                @if(count($roster))
                    @if(hasPermission("homework","evaluate"))<button type="submit" class="btn btn-sm btn--primary">
                        <i class="tio-save mr-1"></i> {{ translate('Save Evaluation') }}
                    </button>@endif
                @endif
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 70px;">{{ translate('Roll No') }}</th>
                                <th>{{ translate('Student Info') }}</th>
                                <th>{{ translate('Submitted File & Notes') }}</th>
                                <th style="width: 220px;">{{ translate('Status') }} <span class="text-danger">*</span></th>
                                <th style="width: 140px;">{{ translate('Marks Obtained') }}</th>
                                <th>{{ translate('Remarks') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roster as $enrollment)
                                @php
                                    $student = $enrollment->student;
                                    $sub = $existing->get($student->id);
                                @endphp
                                <tr>
                                    <td>{{ $enrollment->roll_no ?? '—' }}</td>
                                    <td>
                                        <div class="font-weight-bold text-dark">{{ $student->name }}</div>
                                        <small class="text-muted">{{ translate('Adm No') }}: {{ $student->admission_no }}</small>
                                    </td>
                                    <td>
                                        @if($sub)
                                            <div class="d-flex flex-column">
                                                @if($sub->attachment)
                                                    <a href="{{ asset('storage/app/public/school/homework/' . $sub->attachment) }}" target="_blank" class="text-info text-xs font-weight-medium mb-1">
                                                        <i class="tio-download-to mr-1"></i> {{ translate('Download Submission') }}
                                                    </a>
                                                @endif
                                                
                                                @if($sub->student_notes)
                                                    <span class="text-muted text-xs font-italic" title="{{ $sub->student_notes }}">
                                                        "{{ Str::limit($sub->student_notes, 60) }}"
                                                    </span>
                                                @endif
                                                
                                                @if($sub->submission_date)
                                                    <span class="text-muted text-xxs mt-1">
                                                        {{ translate('Submitted') }}: {{ $sub->submission_date->format('d/m/Y') }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted text-xs">— {{ translate('No submission yet') }} —</span>
                                        @endif
                                    </td>
                                    <td>
                                        <select name="status[{{ $student->id }}]" class="form-control form-control-sm select-status">
                                            <option value="">-- {{ translate('Not Submitted') }} --</option>
                                            @foreach(\App\Models\SchoolHomeworkSubmission::STATUSES as $key => $label)
                                                <option value="{{ $key }}" @selected(($sub?->status ?? ($sub ? 'submitted' : '')) === $key)>
                                                    {{ translate($label) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="{{ $homework->max_marks ?? 9999 }}" 
                                               name="marks[{{ $student->id }}]" 
                                               class="form-control form-control-sm input-marks" 
                                               value="{{ $sub && !is_null($sub->marks_obtained) ? $sub->marks_obtained : '' }}"
                                               placeholder="{{ $homework->max_marks ? 'Max: ' . number_format($homework->max_marks, 2) : '' }}"
                                               {{ $homework->max_marks ? '' : 'disabled' }}>
                                    </td>
                                    <td>
                                        <input type="text" name="remarks[{{ $student->id }}]" 
                                               class="form-control form-control-sm" 
                                               value="{{ $sub?->remarks }}" 
                                               placeholder="{{ translate('Enter feedback/remarks...') }}">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <img src="{{ asset('public/assets/admin/img/900x900/img2.jpg') }}" alt="" class="mb-3" style="width: 80px; opacity: 0.5;">
                                        <div>{{ translate('No students enrolled in this class/section.') }}</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            @if(count($roster))
                <div class="card-footer text-right border-top">
                    @if(hasPermission("homework","evaluate"))<button type="submit" class="btn btn--primary">
                        <i class="tio-save mr-1"></i> {{ translate('Save Evaluation') }}
                    </button>@endif
                </div>
            @endif
        </div>
    </form>
</div>
@endsection

@push('script_2')
<script>
    $(document).on('ready', function() {
        // Automatically set status to 'evaluated' if marks are entered, and vice versa
        $('.input-marks').on('input', function() {
            var $row = $(this).closest('tr');
            var $statusSelect = $row.find('.select-status');
            var val = $(this).val();
            
            if (val !== '' && $statusSelect.val() === '') {
                $statusSelect.val('evaluated');
            }
        });

        $('.select-status').on('change', function() {
            var $row = $(this).closest('tr');
            var $marksInput = $row.find('.input-marks');
            var val = $(this).val();
            
            if (val === '' || val === 'resubmit') {
                $marksInput.val('');
            }
        });
    });
</script>
@endpush
