@extends('layouts.vendor.app')

@section('title', 'Project Details')

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/project-task.css') }}">

    <style>
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 2px solid transparent;
        }

        .nav-tabs .nav-link:hover {
            border-color: transparent;
            border-bottom-color: var(--primary-light);
        }

        .nav-tabs .nav-link.active {
            color: var(--primary);
            border-color: transparent;
            border-bottom-color: var(--primary);
            background-color: transparent;
        }

        .badge-pill {
            padding: 0.35em 0.85em;
        }

        .gap-2>* {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-4">
        {{-- Header --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div class="flex-grow-1 mb-3 mb-md-0">
                        <h1 class="mb-2">{{ $project->project_title }}</h1>
                        <p class="text-muted mb-3">{{ $project->short_description }}</p>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            {{-- <span
                                class="badge badge-pill 
                            @if ($project->progress_status == 'Completed') badge-success
                            @elseif($project->progress_status == 'In Progress') badge-info
                            @elseif($project->progress_status == 'Pending') badge-warning
                            @else badge-secondary @endif">
                                {{ $project->progress_status }}
                            </span> --}}
                            @if (hasPermission('project', 'status_change'))
                                <form action="{{ route('vendor.project.progress-status-change', [$project->id]) }}">
                                    <select name="status" id="" data-placeholder="Status"
                                        onchange="this.form.submit()" class="js-select2-custom">
                                        <option value=""></option>
                                        <option {{ $project->progress_status == 'New' ? 'selected' : '' }} value="New">
                                            New
                                        </option>
                                        <option {{ $project->progress_status == 'Open' ? 'selected' : '' }} value="Open">
                                            Open
                                        </option>
                                        <option {{ $project->progress_status == 'In Progress' ? 'selected' : '' }}
                                            value="In Progress">In Progress</option>
                                        <option {{ $project->progress_status == 'Completed' ? 'selected' : '' }}
                                            value="Completed">
                                            Completed</option>
                                        <option {{ $project->progress_status == 'Cancelled' ? 'selected' : '' }}
                                            value="Cancelled">
                                            Cancelled</option>
                                        <option {{ $project->progress_status == 'On Hold' ? 'selected' : '' }}
                                            value="On Hold">
                                            On
                                            Hold</option>
                                    </select>

                                </form>
                            @endif
                            <span
                                class="badge badge-pill
                            @if ($project->priority == 'High') badge-danger
                            @elseif($project->priority == 'Medium') badge-warning
                            @else badge-success @endif">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                {{ $project->priority }} Priority
                            </span>
                            @if ($project->project_category)
                                <span class="badge badge-pill" style="background-color: var(--primary); color: white;">
                                    {{ $project->project_category }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div>
                        @if (hasPermission('project_task', 'add'))
                            <a href="{{ route('vendor.task.add', $project->id) }}" class="btn text-white"
                                style="background-color: var(--primary);">
                                <i class="tio-add mr-1"></i>
                                Add Task
                            </a>
                        @endif
                        @if (hasPermission('project', 'edit'))
                            <a href="{{ route('vendor.project.edit', $project->id) }}" class="btn text-white"
                                style="background-color: var(--primary);">
                                <i class="fas fa-edit mr-1"></i>
                                Edit Project
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">Project Progress</small>
                        <small class="font-weight-bold">{{ $project->prog_percent }}%</small>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar" role="progressbar"
                            style="width: {{ $project->prog_percent }}%; background-color: var(--primary);"
                            aria-valuenow="{{ $project->prog_percent }}" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <ul class="nav nav-tabs card-header-tabs" id="projectTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link tabs_link {{ !request('tab') || request('tab') == 'overview' ? 'active' : '' }}"
                            id="overview-tab" data-toggle="tab" href="#overview" role="tab">
                            Overview
                        </a>
                    </li>
                    @if (hasAnyPermission(['project_milestone.list', 'project_milestone.add']))
                        <li class="nav-item">
                            <a class="nav-link tabs_link {{ request('tab') && request('tab') == 'milestones' ? 'active' : '' }}"
                                id="milestones-tab" data-toggle="tab" href="#milestones" role="tab">
                                Milestones
                            </a>
                        </li>
                    @endif
                    <li class="nav-item">
                        <a class="nav-link tabs_link {{ request('tab') && request('tab') == 'team' ? 'active' : '' }}"
                            id="team-tab" data-toggle="tab" href="#team" role="tab">
                            Team
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link tabs_link {{ request('tab') && request('tab') == 'financials' ? 'active' : '' }}"
                            id="financials-tab" data-toggle="tab" href="#financials" role="tab">
                            Financials
                        </a>
                    </li>
                    @if (hasAnyPermission(['project_internal_note.list', 'project_internal_note.add']))
                        <li class="nav-item">
                            <a class="nav-link tabs_link {{ request('tab') && request('tab') == 'notes' ? 'active' : '' }}"
                                id="notes-tab" data-toggle="tab" href="#notes" role="tab">
                                Notes
                            </a>
                        </li>
                    @endif
                    @if (hasAnyPermission(['project_task.list', 'project_task.add']))
                        <li class="nav-item">
                            <a class="nav-link tabs_link {{ request('tab') && request('tab') == 'tasks' ? 'active' : '' }}"
                                id="tasks-tab" data-toggle="tab" href="#tasks" role="tab">
                                Tasks
                            </a>
                        </li>
                    @endif
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content" id="projectTabContent">
                    {{-- Overview Tab --}}
                    <div class="tab-pane fade {{ !request('tab') || request('tab') == 'overview' ? 'show active' : '' }}"
                        id="overview" role="tabpanel">
                        <div class="row">
                            {{-- Project Details --}}
                            <div class="col-md-6">
                                <h5 class="mb-4 font-weight-bold">Project Details</h5>

                                <div class="mb-3 d-flex align-items-start">
                                    <i class="fas fa-user-tie text-primary mt-1 mr-3"></i>
                                    <div class="flex-grow-1">
                                        <label class="small font-weight-bold text-muted mb-1">Client</label>
                                        <p class="mb-0">
                                            <b>{{ $project->client?->f_name . ' ' . $project->client?->l_name }}</b>
                                        </p>
                                        <p>{{ $project->client?->phone }}</p>
                                    </div>
                                </div>

                                <div class="mb-3 d-flex align-items-start">
                                    <i class="fas fa-calendar text-primary mt-1 mr-3"></i>
                                    <div class="flex-grow-1">
                                        <label class="small font-weight-bold text-muted mb-1">Timeline</label>
                                        <p class="mb-0">
                                            {{ \Carbon\Carbon::parse($project->start_date)->format('M d, Y') }} to
                                            {{ \Carbon\Carbon::parse($project->end_date)->format('M d, Y') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mb-3 d-flex align-items-start">
                                    <i class="fas fa-clock text-primary mt-1 mr-3"></i>
                                    <div class="flex-grow-1">
                                        <label class="small font-weight-bold text-muted mb-1">Duration</label>
                                        <p class="mb-0">{{ $project->duration_count }} {{ $project->duration_type }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mb-3 d-flex align-items-start">
                                    <i class="fas fa-file-alt text-primary mt-1 mr-3"></i>
                                    <div class="flex-grow-1">
                                        <label class="small font-weight-bold text-muted mb-1">Project Type</label>
                                        <p class="mb-0">{{ $project->project_type }} - {{ $project->project_size }}</p>
                                    </div>
                                </div>

                                @if ($project->tags_labels)
                                    <div class="mb-3 d-flex align-items-start">
                                        <i class="fas fa-tags text-primary mt-1 mr-3"></i>
                                        <div class="flex-grow-1">
                                            <label class="small font-weight-bold text-muted mb-1">Tags</label>
                                            <div class="d-flex flex-wrap">
                                                @foreach (explode(',', $project->tags_labels) as $tag)
                                                    <span
                                                        class="badge badge-secondary mr-1 mb-1">{{ trim($tag) }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($project->file)
                                    <div class="mb-3 d-flex align-items-start">
                                        <i class="fas fa-file-download text-primary mt-1 mr-3"></i>
                                        <div class="flex-grow-1">
                                            <label class="small font-weight-bold text-muted mb-1">Project File</label>
                                            <p class="mb-0">
                                                <a href="{{ asset('storage/app/public/project/' . $project->file) }}"
                                                    style="color: var(--primary);" target="_blank">
                                                    <i class="fas fa-download mr-1"></i>{{ basename($project->file) }}
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Specifications --}}
                            <div class="col-md-6">
                                <h5 class="mb-4 font-weight-bold">Short Description</h5>
                                <p class="text-muted">{{ $project->short_description }}</p>
                                <h5 class="mb-4 font-weight-bold">Specifications</h5>
                                <p class="text-muted">{{ $project->specifications }}</p>

                                @if ($project->attachments && $project->attachments->count() > 0)
                                    <div class="mt-4">
                                        <h6 class="font-weight-bold mb-3">Attachments</h6>
                                        <div class="list-group">
                                            @foreach ($project->attachments as $attachment)
                                                <a href="{{ asset('storage/app/public/' . $attachment->file_path . '/' . $attachment->file_name) }}"
                                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                                    target="_blank">
                                                    <span>
                                                        <i class="fas fa-paperclip mr-2"></i>
                                                        {{ $attachment->file_name }}
                                                    </span>
                                                    <small
                                                        class="text-muted">{{ $attachment->created_at->format('M d, Y') }}</small>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if (hasAnyPermission(['project_milestone.list', 'project_milestone.add']))
                        {{-- Milestones Tab --}}

                        <div class="tab-pane fade {{ request('tab') && request('tab') == 'milestones' ? 'show active' : '' }}"
                            id="milestones" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="mb-0 font-weight-bold">Project Milestones</h5>
                                @if (hasPermission('project_milestone', 'add'))
                                    <button type="button" data-toggle="modal" data-target="#milestoneModal"
                                        class="btn btn-sm text-white" style="background-color: var(--primary);">
                                        <i class="fas fa-plus mr-1"></i>Add Milestone
                                    </button>
                                    @include('vendor-views.form_modals.milestone_modal')
                                @endif
                            </div>
                            @if (hasPermission('project_milestone', 'list'))

                                @if ($project->milestones && $project->milestones->count() > 0)
                                    <div class="list-group">
                                        @foreach ($project->milestones as $milestone)
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1 font-weight-bold">{{ $milestone->title }}</h6>
                                                        <small class="text-muted">
                                                            <i class="far fa-calendar mr-1"></i>
                                                            Due:
                                                            {{ \Carbon\Carbon::parse($milestone->due_date)->format('M d, Y') }}
                                                        </small>
                                                    </div>
                                                    <div class="text-right d-flex gap-2">
                                                        {{-- <span
                                                    class="badge 
                                                    @if ($milestone->status == 'Completed') badge-success
                                                    @elseif($milestone->status == 'In Progress') badge-info
                                                    @else badge-warning @endif">
                                                    {{ $milestone->status }}
                                                </span> --}}
                                                        @if (hasPermission('project_milestone', 'status_change'))
                                                            <form
                                                                action="{{ route('vendor.project.milestone.status-change', [$milestone->id]) }}">
                                                                <select name="status" id=""
                                                                    data-placeholder="Status"
                                                                    onchange="this.form.submit()"
                                                                    class="js-select2-custom">
                                                                    <option value=""></option>
                                                                    <option
                                                                        {{ $milestone->status == 'Pending' ? 'selected' : '' }}
                                                                        value="Pending">Pending</option>
                                                                    <option
                                                                        {{ $milestone->status == 'In Progress' ? 'selected' : '' }}
                                                                        value="In Progress">In Progress</option>
                                                                    <option
                                                                        {{ $milestone->status == 'Completed' ? 'selected' : '' }}
                                                                        value="Completed">Completed</option>

                                                                </select>

                                                            </form>
                                                        @endif
                                                        @if (hasPermission('project_milestone', 'delete'))
                                                            <a class="text-danger form-alert" href="javascript:"
                                                                data-id="category-{{ $milestone['id'] }}"
                                                                data-message="{{ translate('Want to delete this milestone') }}"
                                                                title="{{ translate('messages.delete_milestone') }}"><i
                                                                    class="tio-delete"></i></a>

                                                            <form
                                                                action="{{ route('vendor.project.milestone.delete', [$milestone['id']]) }}"
                                                                method="get" id="category-{{ $milestone['id'] }}">
                                                                @csrf @method('get')
                                                            </form>
                                                        @endif

                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center text-muted py-5">
                                        <i class="fas fa-tasks fa-3x mb-3 d-block"></i>
                                        <p>No milestones added yet.</p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif
                    {{-- Team Tab --}}
                    <div class="tab-pane fade {{ request('tab') && request('tab') == 'team' ? 'show active' : '' }}"
                        id="team" role="tabpanel">
                        <div class="mb-4">
                            <h5 class="mb-3 font-weight-bold">Team Leadership</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-2 text-muted">Project Manager</h6>
                                            <a href="{{ $project?->projectManager ? route('vendor.employee.view', [$project?->projectManager?->id]) : 'javascript:;' }}"
                                                class="d-flex gap-2 align-items-center">
                                                <img style="height: 37px;width: 37px;border-radius: 50%;"
                                                    src="{{ asset('storage/app/public/vendor') . '/' . $project?->projectManager?->image }}"
                                                    alt="">
                                                <p class="card-text font-weight-bold mb-0">
                                                    {{ $project->projectManager?->f_name . ' ' . $project->projectManager?->l_name }}
                                                </p>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h6 class="card-subtitle mb-2 text-muted">Team Members</h6>
                                                <button class="btn btn-outline-primary btn-sm" type="button"
                                                    data-toggle="modal" data-target="#teamModal">Edit Team</button>
                                            </div>
                                            @if ($project->teamMembers && $project->teamMembers->count() > 0)
                                                @foreach ($project->teamMembers as $member)
                                                    <a href="{{ $member->employee ? route('vendor.employee.view', [$member->employee?->id]) : '#' }}"
                                                        class="d-flex gap-2 align-items-center">
                                                        <img style="height: 37px; width: 37px; border-radius: 50%; object-fit: cover; "
                                                            src="{{ asset('storage/app/public/vendor') . '/' . $member->employee?->image }}"
                                                            alt="">
                                                        <p class="card-text font-weight-bold mb-0">
                                                            {{ $member->employee?->f_name . ' ' . $member->employee?->l_name }}
                                                        </p>
                                                    </a>
                                                @endforeach
                                            @else
                                                No members yet
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @include('vendor-views.form_modals.team_modal')
                            </div>
                        </div>

                        @if ($project->teams)
                            <div>
                                <h5 class="mb-3 font-weight-bold">Team Structure</h5>
                                @php
                                    $teams = json_decode($project->teams, true);
                                @endphp
                                @if ($teams)
                                    <div class="row">
                                        @foreach ($teams as $team)
                                            <div class="col-md-6 mb-3">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h6 class="card-title font-weight-bold">{{ $team['name'] }}</h6>
                                                        <p class="card-text text-muted mb-0">
                                                            <i class="fas fa-users mr-1"></i>
                                                            {{ count($team['members']) }} members
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Financials Tab --}}
                    <div class="tab-pane fade {{ request('tab') && request('tab') == 'financials' ? 'show active' : '' }}"
                        id="financials" role="tabpanel">
                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <div class="card text-center">
                                    <div class="card-body">

                                        <h6 class="card-subtitle mb-2 text-muted">Total Cost</h6>
                                        <h3 class="mb-0">{{ _price($project->cost, 0) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-muted">Advance Payment</h6>
                                        <h3 class="mb-0 text-success">{{ _price($project->advance_pay, 0) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-muted">Remaining</h6>
                                        <h3 class="mb-0" style="color: var(--primary);">
                                            {{ _price($project->cost - $project->advance_pay, 0) }}
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title font-weight-bold">Payment Terms</h6>
                                <p class="card-text">{{ $project->payment_terms }}</p>
                                <hr>
                                <p class="mb-0 text-muted"><strong>Type:</strong> {{ translate($project->payment_type) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @if (hasAnyPermission(['project_internal_note.list', 'project_internal_note.add']))

                        {{-- Notes Tab --}}
                        <div class="tab-pane fade {{ request('tab') && request('tab') == 'notes' ? 'show active' : '' }}"
                            id="notes" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="mb-0 font-weight-bold">Internal Notes</h5>
                                @if (hasPermission('project_internal_note', 'add'))
                                    <button type="button" class="btn btn-sm text-white"
                                        style="background-color: var(--primary);" onclick="toggleNoteForm()">
                                        <i class="fas fa-plus mr-1"></i>Add Note
                                    </button>
                                @endif
                            </div>
                            @if (hasPermission('project_internal_note', 'add'))
                                {{-- Add Note Form --}}
                                <div id="note-form" style="display: none;">
                                    <div class="card bg-light mb-3">
                                        <div class="card-body">
                                            <form action="{{ route('vendor.project.note.store') }}" method="POST">
                                                @csrf
                                                <div class="form-group">
                                                    <textarea name="note" rows="3" class="form-control" placeholder="Enter your note here..." required></textarea>
                                                </div>
                                                <input type="hidden" name="project_id" value="{{ $project->id }}">
                                                <button type="submit" class="btn btn-sm text-white mr-2"
                                                    style="background-color: var(--primary);">
                                                    <i class="fas fa-save mr-1"></i>Save Note
                                                </button>
                                                <button type="button" onclick="toggleNoteForm()"
                                                    class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-times mr-1"></i>Cancel
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if (hasPermission('project_internal_note', 'list'))

                                @if ($project->internalNotes)
                                    @foreach ($project->internalNotes as $key => $note)
                                        <div class="list-group my-1">
                                            <div class="list-group-item list-group-item-warning">
                                                <p class="mb-2">{{ $note->note }}</p>
                                                <small class="text-muted">
                                                    <i class="far fa-clock mr-1"></i>
                                                    {{ $note->created_at->format('M d, Y h:i A') }}
                                                </small>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center text-muted py-5">
                                        <i class="fas fa-sticky-note fa-3x mb-3 d-block"></i>
                                        <p>No notes added yet.</p>
                                    </div>
                                @endif
                            @endif

                        </div>
                    @endif
                    @if (hasAnyPermission(['project_task.list', 'project_task.add']))

                        {{-- Tasks Tab --}}
                        <div class="tab-pane fade {{ request('tab') && request('tab') == 'tasks' ? 'show active' : '' }}"
                            id="tasks" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="mb-0 font-weight-bold">Tasks</h5>
                                @if (hasPermission('project_task', 'add'))
                                    <a href="{{ route('vendor.project.task.add', [$project->id]) }}" type="button"
                                        class="btn btn-sm text-white" style="background-color: var(--primary);" \>
                                        <i class="fas fa-plus mr-1"></i>Add Task
                                    </a>
                                @endif
                            </div>
                            @php $tasks = $project->tasks; @endphp
                            @if (hasPermission('project_task', 'list'))


                                <!-- Tasks Grid -->
                                <div class="ptask-grid">
                                    @php $common_statuses = ['completed', 'in-progress', 'new', 'cancelled']; @endphp
                                    @foreach ($tasks as $key => $task)
                                        @php
                                            $slug_status = \Illuminate\Support\Str::slug($task->status);
                                            $staus_class = in_array($slug_status, $common_statuses)
                                                ? $slug_status
                                            : 'other'; @endphp
                                        <div class="ptask-card">
                                            <div class="dropdown">
                                                <button class="btn p-1 dropdown-toggle"
                                                    style="position: absolute; right: -14px;top: -11px;" type="button"
                                                    data-toggle="dropdown" aria-expanded="false">
                                                    <img style="width: 24px; filter: contrast(0)"
                                                        src="{{ asset('storage/app/public/util/10025520.png') }}"
                                                        alt="action" />
                                                </button>
                                                <div class="dropdown-menu">
                                                    @if (hasPermission('project_task', 'view'))
                                                        <a href="{{ route('vendor.project.task.detail', [$task->id]) }}"
                                                            class="dropdown-item text-primary" title="View">
                                                            <i class="tio-visible"></i>View
                                                        </a>
                                                    @endif
                                                    @if (hasPermission('project_task', 'delete'))
                                                        <a class="dropdown-item text-danger form-alert" href="javascript:"
                                                            data-id="category-{{ $task['id'] }}"
                                                            data-message="{{ translate('Want to delete this ad') }}"
                                                            title="{{ translate('messages.delete_ad') }}"><i
                                                                class="fas fa-trash-alt"></i>
                                                            Delete
                                                        </a>
                                                        <form
                                                            action="{{ route('vendor.project.task.delete', [$task['id']]) }}"
                                                            method="get" id="category-{{ $task['id'] }}">
                                                            @csrf @method('get')
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="ptask-card-top">
                                                <a href="#"
                                                    class="ptask-status-badge ptask-status-{{ $staus_class }}">
                                                    <i class="fas fa-check-circle"></i> {{ $task->status }}
                                                </a>
                                            </div>
                                            <div class="ptask-title one-line-ellipsis">{{ ucfirst($task->title) }}</div>
                                            <div class="ptask-meta">
                                                <div class="ptask-meta-item">
                                                    <i class="fas fa-user"></i>
                                                    @if ($task->employee_id == null && $task->offered_to != null)
                                                        {{ $task->offeredTo?->f_name . ' ' . $task->offeredTo?->l_name . ' (Id: ' . $task->offeredTo?->id . ')' }}
                                                    @else
                                                        {{ $task->employee_id === 0 ? 'Self' : $task->employee?->f_name . ' ' . $task->employee?->l_name . ' (Id: ' . $task->employee?->id . ')' }}
                                                    @endif
                                                </div>
                                                <div class="ptask-meta-item">
                                                    <i class="fas fa-calendar"></i>
                                                    {{ _formatted_date($task->created_at) }}
                                                </div>
                                            </div>
                                            <div class="ptask-actions">
                                                <span
                                                    class="ptask-priority ptask-priority-medium">{{ $task->progress }}%</span>
                                                @php
                                                $task_closed = in_array($task->status, ['Completed', 'Cancelled']); @endphp
                                                @if (
                                                    !$task_closed &&
                                                        hasPermission('project_task', 'edit') &&
                                                        ($task->employee_id == \App\CentralLogics\Helpers::get_loggedin_user()->id ||
                                                            hasPermission('project_task', 'edit_others')))
                                                    <a href="{{ route('vendor.project.task.edit', [$task->id]) }}"
                                                        class="ptask-btn ptask-btn-edit " title="Edit">
                                                        <i class="tio-edit"></i> Edit
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach

                                </div>
                                @if (hasPermission('project_task', 'add') && !count($tasks))
                                    <div class="w-100 d-flex align-items-center justify-content-center flex-column">
                                        No Tasks Yet..
                                        <a href="{{ route('vendor.project.task.add', [$project?->id]) }}"
                                            class="btn btn-primary mt-5">Add Task</a>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>




@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
    @include('vendor-views.js.project_milestone_add')

    <script>
        function toggleNoteForm() {
            const form = document.getElementById('note-form');
            if (form.style.display === 'none') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
    </script>
    <script>
        "use strict";
        $(".status_form_alert").on("click", function(e) {
            const id = $(this).data('id');
            const message = $(this).data('message');
            e.preventDefault();
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $('#' + id).submit()
                }
            })
        })

        $(document).on('click', '.status_change', function() {
            var task_id = $(this).attr('data-id');
            var status = $(this).attr('data-status');
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "",
                data: {
                    task_id: task_id,
                    status: status
                },
                success: function(data) {
                    var url = window.location.href;
                    $('.close').click();
                    $('.task_table').load(url + ' .task_table_inner')
                },
            });
        })
        $(document).on('submit', "#save_task", function(e) {
            e.preventDefault();
            var formData = new FormData($(this)[0]);

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: $(this).attr('action'),
                data: formData,
                contentType: false,
                processData: false,
                cache: false,
                success: function(data) {
                    if (data.status) {
                        var url = window.location.href;
                        toastr.success(data.message);
                        $('.task_table').load(url + ' .task_table_inner')
                        $('.comments_table').load(url + ' .comments_table_inner')
                        $('.close').click();
                        $("#save_task").trigger('reset')
                    } else {
                        toastr.error(data.message);
                    }
                },
            });
        })

        $(".tabs_link").on('click', function() {
            var id = $(this).attr('id'); // example: milestones-tab
            var tab = id.split("-")[0]; // "milestones"

            var full = window.location.pathname.split("/");

            var baseUrl = full.slice(0, 4).join("/");
            console.log(baseUrl)

            var newUrl = baseUrl + "/" + tab;

            history.pushState({}, "", newUrl);
        });
    </script>
@endpush
