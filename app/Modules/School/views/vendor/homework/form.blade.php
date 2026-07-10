@extends('layouts.vendor.app')
@section('title', $homework ? translate('Edit Homework Assignment') : translate('New Homework Assignment'))

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <i class="tio-agenda mr-1"></i>  
            {{ $homework ? translate('Edit Homework Assignment') : translate('New Homework Assignment') }}
        </h1>
        <a href="{{ route('vendor.school.homework.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-back-ui"></i> {{ translate('Back to List') }}
        </a>
    </div> 

    <form action="{{ $homework ? route('vendor.school.homework.update', $homework->id) : route('vendor.school.homework.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <!-- Left Panel -->
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <!-- Homework Title -->
                        <div class="form-group">
                            <label class="input-label font-weight-bold" for="title">{{ translate('Title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $homework?->title) }}" placeholder="{{ translate('e.g. Algebra Homework 1') }}" required maxlength="191">
                            @error('title')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Homework Description -->
                        <div class="form-group mb-0">
                            <label class="input-label font-weight-bold" for="description">{{ translate('Description / Instructions') }}</label>
                            <textarea name="description" id="description" class="form-control" rows="8" placeholder="{{ translate('Write homework details, instructions, or reading tasks here...') }}">{{ old('description', $homework?->description) }}</textarea>
                            @error('description')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <!-- Class Select -->
                        <div class="form-group">
                            <label class="input-label font-weight-bold">{{ translate('Class') }} <span class="text-danger">*</span></label>
                            <select name="school_class_id" id="school_class_id" class="form-control js-select2-custom" required>
                                <option value="">{{ translate('Select Class') }}</option>
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}" @selected(old('school_class_id', $homework?->school_class_id) == $c->id)>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('school_class_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Section Select -->
                        <div class="form-group">
                            <label class="input-label font-weight-bold">{{ translate('Section') }}</label>
                            <select name="class_section_id" id="class_section_id" class="form-control js-select2-custom">
                                <option value="" data-class="">{{ translate('All Sections') }}</option>
                                @foreach($sections as $s)
                                    <option value="{{ $s->id }}" data-class="{{ $s->school_class_id }}" @selected(old('class_section_id', $homework?->class_section_id) == $s->id)>
                                        {{ $s->schoolClass?->name }} - {{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('class_section_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Subject Select -->
                        <div class="form-group">
                            <label class="input-label font-weight-bold">{{ translate('Subject') }} <span class="text-danger">*</span></label>
                            <select name="subject_id" id="subject_id" class="form-control js-select2-custom" required>
                                <option value="">{{ translate('Select Subject') }}</option>
                                @foreach($subjects as $sub)
                                    <option value="{{ $sub->id }}" @selected(old('subject_id', $homework?->subject_id) == $sub->id)>
                                        {{ $sub->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('subject_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <hr>

                        <!-- Dates -->
                        <div class="form-group">
                            <label class="input-label font-weight-bold">{{ translate('Assign Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="assign_date" class="form-control" value="{{ old('assign_date', $homework?->assign_date ? $homework->assign_date->format('Y-m-d') : now()->toDateString()) }}" required>
                            @error('assign_date')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="input-label font-weight-bold">{{ translate('Submission Due Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="submission_date" class="form-control" value="{{ old('submission_date', $homework?->submission_date ? $homework->submission_date->format('Y-m-d') : now()->addDays(2)->toDateString()) }}" required>
                            @error('submission_date')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Max Marks -->
                        <div class="form-group">
                            <label class="input-label font-weight-bold">{{ translate('Max Marks (optional)') }}</label>
                            <input type="number" step="0.01" name="max_marks" class="form-control" min="0" value="{{ old('max_marks', $homework?->max_marks) }}" placeholder="{{ translate('e.g. 10.00') }}">
                            @error('max_marks')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Attachment -->
                        <div class="form-group">
                            <label class="input-label font-weight-bold">{{ translate('Attachment') }}</label>
                            <div class="custom-file">
                                <input type="file" name="attachment" id="homeworkAttachment" class="custom-file-input">
                                <label class="custom-file-label" for="homeworkAttachment">{{ translate('Choose file') }}</label>
                            </div>
                            <small class="text-muted d-block mt-1">
                                {{ translate('Allowed: PDF, DOC, DOCX, ZIP, JPG, JPEG, PNG, WEBP (Max 8MB)') }}
                            </small>
                            @if($homework && $homework->attachment)
                                <div class="mt-2 p-2 bg-light rounded d-flex align-items-center justify-content-between border">
                                    <span class="text-truncate text-xs font-weight-medium" style="max-width: 200px;">
                                        <i class="tio-document-text mr-1"></i> {{ $homework->attachment }}
                                    </span>
                                    <a href="{{ asset('storage/app/public/school/homework/' . $homework->attachment) }}" target="_blank" class="btn btn-xs btn-outline-info">
                                        <i class="tio-download-to"></i> {{ translate('View') }}
                                    </a>
                                </div>
                            @endif
                            @error('attachment')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn--primary">
                            <i class="tio-save mr-1"></i>
                            {{ $homework ? translate('Update Assignment') : translate('Save Assignment') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('script_2')
<script>
    $(document).on('ready', function() {
        var $classSelect = $('#school_class_id');
        var $sectionSelect = $('#class_section_id');
        
        // Clone all options from section dropdown to keep a master list
        var $sectionOptions = $sectionSelect.find('option').clone();

        function filterSections() {
            var selectedClass = $classSelect.val();
            
            // Empty the dropdown and append only matching options
            $sectionSelect.empty();
            
            $sectionOptions.each(function() {
                var optionClass = $(this).data('class');
                // Always append the "All Sections" option (empty value), or matches of selectedClass
                if (!optionClass || !selectedClass || String(optionClass) === String(selectedClass)) {
                    $sectionSelect.append($(this).clone());
                }
            });

            // If selected class changed, check if current value is still valid; if not, reset to empty
            var currentVal = "{{ old('class_section_id', $homework?->class_section_id) }}";
            if (currentVal && $sectionSelect.find('option[value="' + currentVal + '"]').length > 0) {
                $sectionSelect.val(currentVal);
            } else {
                $sectionSelect.val('');
            }
            
            // Re-trigger select2 to update its view
            $sectionSelect.trigger('change');
        }

        // Bind change event to class selector
        $classSelect.on('change', function() {
            filterSections();
        });

        // Run initial filter
        filterSections();

        // Custom File Input label update
        $('.custom-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        });
    });
</script>
@endpush
