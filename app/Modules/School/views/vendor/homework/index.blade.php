@extends('layouts.vendor.app')
@section('title', 'Homework')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
 
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-agenda mr-1"></i> {{ translate('Homework / Assignments') }}</h1>
        @if(hasPermission("homework","add"))<a href="{{ route('vendor.school.homework.create') }}" class="btn btn-sm btn--primary"><i class="tio-add"></i> {{ translate('New Assignment') }}</a>@endif
    </div>

    <!-- Filters -->
    @if(hasPermission("homework","view") || hasPermission("homework","evaluate"))<div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('vendor.school.homework.index') }}" class="form-row align-items-end" id="filterForm">
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="input-label mb-1">{{ translate('Class') }}</label>
                    <select name="class_id" id="filterClass" class="form-control form-control-sm js-select2-custom">
                        <option value="">{{ translate('All Classes') }}</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" @selected((string)$classId === (string)$c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="input-label mb-1">{{ translate('Section') }}</label>
                    <select name="section_id" id="filterSection" class="form-control form-control-sm js-select2-custom">
                        <option value="" data-class="">{{ translate('All Sections') }}</option>
                        @foreach($sections as $s)
                            <option value="{{ $s->id }}" data-class="{{ $s->school_class_id }}" @selected((string)$sectionId === (string)$s->id)>
                                {{ $s->schoolClass?->name }} - {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="input-label mb-1">{{ translate('Subject') }}</label>
                    <select name="subject_id" class="form-control form-control-sm js-select2-custom">
                        <option value="">{{ translate('All Subjects') }}</option>
                        @foreach($subjects as $sub)
                            <option value="{{ $sub->id }}" @selected((string)$subjectId === (string)$sub->id)>{{ $sub->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="input-label mb-1">{{ translate('Search Title') }}</label>
                    <div class="input-group input-group-sm">
                        <input name="search" value="{{ $search }}" class="form-control" placeholder="{{ translate('Search by title...') }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn--primary"><i class="tio-search"></i></button>
                            @if($classId || $sectionId || $subjectId || $search)
                                <a href="{{ route('vendor.school.homework.index') }}" class="btn btn-secondary"><i class="tio-clear"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>@endif

    <!-- Homework List -->
    @if(hasPermission("homework","view") || hasPermission("homework","evaluate"))
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Homework Title') }}</th>
                            <th>{{ translate('Class & Section') }}</th>
                            <th>{{ translate('Subject') }}</th>
                            <th>{{ translate('Dates') }}</th>
                            <th>{{ translate('Max Marks') }}</th>
                            <th class="text-center">{{ translate('Submissions') }}</th>
                            <th class="text-center">{{ translate('Attachment') }}</th>
                            <th class="text-right">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($homeworks as $hw)
                            <tr>
                                <td>
                                    <div class="font-weight-bold">
                                        <a href="{{ route('vendor.school.homework.submissions', $hw->id) }}" class="text-hover-primary">
                                            {{ $hw->title }}
                                        </a>
                                    </div>
                                    @if($hw->description)
                                        <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;" title="{{ $hw->description }}">
                                            {{ $hw->description }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-soft-info font-weight-medium">
                                        {{ $hw->schoolClass?->name }}
                                        @if($hw->classSection)
                                            - {{ $hw->classSection->name }}
                                        @else
                                            ({{ translate('All Sections') }})
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <span class="font-weight-medium">{{ $hw->subject?->name ?? '—' }}</span>
                                </td>
                                <td>
                                    <div class="text-xs">
                                        <strong>{{ translate('Assign') }}:</strong> {{ $hw->assign_date ? $hw->assign_date->format('d/m/Y') : '—' }}
                                    </div>
                                    <div class="text-xs text-danger mt-1">
                                        <strong>{{ translate('Due') }}:</strong> {{ $hw->submission_date ? $hw->submission_date->format('d/m/Y') : '—' }}
                                    </div>
                                </td>
                                <td>
                                    <span class="font-weight-bold text-dark">{{ $hw->max_marks ? number_format($hw->max_marks, 2) : '—' }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('vendor.school.homework.submissions', $hw->id) }}" class="badge badge-soft-primary p-2">
                                        <i class="tio-receipt mr-1"></i> {{ $hw->submissions_count }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    @if($hw->attachment)
                                        <a href="{{ asset('storage/app/public/school/homework/' . $hw->attachment) }}" target="_blank" class="btn btn-outline-info btn-xs" title="{{ translate('Download attachment') }}">
                                            <i class="tio-download-to"></i>
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="dropdown sch-actions">
                                        <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport">
                                            <i class="fa fa-bars"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            @if(hasPermission("homework","view") || hasPermission("homework","evaluate"))<a class="dropdown-item" href="{{ route('vendor.school.homework.submissions', $hw->id) }}">
                                                <i class="tio-receipt"></i> {{ translate('Evaluate Submissions') }}
                                            </a>@endif
                                            @if(hasPermission("homework","edit"))<a class="dropdown-item" href="{{ route('vendor.school.homework.edit', $hw->id) }}">
                                                <i class="tio-edit"></i> {{ translate('Edit') }}
                                            </a>@endif
                                            <div class="dropdown-divider"></div>
                                            @if(hasPermission("homework","delete"))<a class="dropdown-item text-danger" href="{{ route('vendor.school.homework.delete', $hw->id) }}" onclick="return confirm('{{ translate('Are you sure you want to delete this homework? All student submissions will also be deleted.') }}')">
                                                <i class="tio-delete"></i> {{ translate('Delete') }}
                                            </a>@endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <img src="{{ asset('public/assets/admin/img/900x900/img2.jpg') }}" alt="" class="mb-3" style="width: 80px; opacity: 0.5;">
                                    <div>{{ translate('No homework assignments found.') }}</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @if(count($homeworks))
        <div class="mt-3 px-2">
            {!! $homeworks->links() !!}
        </div>
    @endif
    @endif
</div>
@endsection

@push('script_2')
<script>
    $(document).on('ready', function() {
        var $classSelect = $('#filterClass');
        var $sectionSelect = $('#filterSection');
        
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
            var currentVal = $sectionSelect.val();
            if (currentVal) {
                var exists = $sectionSelect.find('option[value="' + currentVal + '"]').length > 0;
                if (!exists) {
                    $sectionSelect.val('');
                }
            }
             
            // Re-trigger select2 to update its view
            $sectionSelect.trigger('change');
        }

        // Bind change event to class selector 
        $classSelect.on('change', function() {
            filterSections();
        });

        // Initial filter run in case class was pre-selected
        if ($classSelect.val()) {
            filterSections();
        }
    });
</script>
@endpush
