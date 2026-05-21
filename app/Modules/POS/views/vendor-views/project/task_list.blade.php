@extends('layouts.vendor.app')

@section('title', 'Project Tasks')

@push('css_or_js')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/project-task.css') }}">
@endpush

@section('content')
    <div class="container-fluid py-4">

        <div class="ptask-wrapper">
            <div class="ptask-container">
                <div class="d-flex justify-content-between">
                    @if (isset($project))
                        <h1>Tasks of Project : {{ $project?->project_title }}</h1>
                    @else
                        <h1>Project Tasks</h1>
                    @endif
                    @if (hasPermission('project_task', 'add'))
                        <button class="btn btn-primary">Add Task</button>
                    @endif
                </div>
                @if (hasPermission('project_task', 'list'))

                    <!-- Header with filters -->
                    <div class="ptask-stats mb-2">
                        <div class="ptask-stat-item">
                            <div class="ptask-stat-number">
                                {{ $tasks->filter(fn($t) => strtolower($t->status) === 'new')->count() }}</div>
                            <div class="ptask-stat-label">New</div>
                        </div>
                        <div class="ptask-stat-item">
                            <div class="ptask-stat-number">{{ count($tasks) }}</div>
                            <div class="ptask-stat-label">Total Tasks</div>
                        </div>
                        <div class="ptask-stat-item">
                            <div class="ptask-stat-number">
                                {{ $tasks->filter(fn($t) => strtolower($t->status) === 'pending')->count() }}</div>
                            <div class="ptask-stat-label">Pending</div>
                        </div>
                        <div class="ptask-stat-item">
                            <div class="ptask-stat-number">
                                {{ $tasks->filter(fn($t) => strtolower($t->status) === 'in progress')->count() }}</div>
                            <div class="ptask-stat-label">In Progress</div>
                        </div>
                        <div class="ptask-stat-item">
                            <div class="ptask-stat-number">
                                {{ $tasks->filter(fn($t) => strtolower($t->status) === 'completed')->count() }}</div>
                            <div class="ptask-stat-label">Completed</div>
                        </div>
                    </div>
                    <!-- Tasks Grid -->
                    <div class="ptask-grid">
                        @php $common_statuses = ['completed', 'in-progress', 'new', 'cancelled']; @endphp
                        @foreach ($tasks as $key => $task)
                            @php
                                $slug_status = \Illuminate\Support\Str::slug($task->status);
                            $staus_class = in_array($slug_status, $common_statuses) ? $slug_status : 'other'; @endphp
                            <div class="ptask-card">
                                @if (hasAnyPermission(['project_task.view', 'project_task.delete']))
                                    <div class="dropdown">
                                        <button class="btn p-1 dropdown-toggle"
                                            style="position: absolute; right: -14px;top: -11px;" type="button"
                                            data-toggle="dropdown" aria-expanded="false">
                                            <img style="width: 24px; filter: contrast(0)"
                                                src="{{ asset('storage/app/public/util/10025520.png') }}" alt="action" />
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
                                                <form action="{{ route('vendor.project.task.delete', [$task['id']]) }}"
                                                    method="get" id="category-{{ $task['id'] }}">
                                                    @csrf @method('get')
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div class="ptask-card-top">
                                    <a href="#" class="ptask-status-badge ptask-status-{{ $staus_class }}">
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
                                        <i class="fas fa-calendar"></i> {{ _formatted_date($task->created_at) }}
                                    </div>
                                </div>
                                <div class="ptask-actions">
                                    <span class="ptask-priority ptask-priority-medium">{{ $task->progress }}%</span>
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
                <!-- Statistics -->
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
@endpush
