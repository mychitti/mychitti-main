@extends('layouts.vendor.app')
@section('title', 'Doctors')

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h1 class="page-header-title mb-2">
                    <span class="page-header-icon"><i class="tio-user-outlined" style="font-size:22px;"></i></span>
                    <span>Doctors <span class="badge badge-soft-dark ml-2">{{ $doctors->total() }}</span></span>
                </h1>
                <a href="{{ route('vendor.doctor.create') }}" class="btn btn--primary mb-2">
                    <i class="tio-add-circle"></i> Add Doctor
                </a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>Department</th>
                            <th>OPD Room</th>
                            <th>Consultation Fee</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($doctors as $key => $doctor)
                            <tr>
                                <td>{{ $doctors->firstItem() + $key }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if ($doctor->employee?->image)
                                            <img class="avatar avatar-sm mr-2 rounded-circle onerror-image "
                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                    $doctor->employee->image ?? '',
                                                    asset('storage/vendor') . '/' . $doctor->employee->image ?? '',
                                                    asset('public/assets/admin/img/160x160/img1.jpg'),
                                                    'vendor/',
                                                ) }}"
                                                alt="Doctor Image">
                                        @else
                                            <span class="avatar avatar-sm avatar-soft-primary avatar-circle mr-2">
                                                <span
                                                    class="avatar-initials">{{ strtoupper(substr($doctor->employee?->f_name ?? 'D', 0, 1)) }}</span>
                                            </span>
                                        @endif
                                        <div>
                                            <span class="d-block font-weight-bold">Dr. {{ $doctor->employee?->f_name }}
                                                {{ $doctor->employee?->l_name }}</span>
                                            <small class="text-muted">{{ $doctor->registration_number }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $doctor->specialization }}</td>
                                <td>{{ $doctor->department ?? '—' }}</td>
                                <td>{{ $doctor->opd_room ?? '—' }}</td>
                                <td>{{ number_format($doctor->consultation_fee, 2) }}</td>
                                <td>
                                    <button class="btn p-1 dropdown-toggle" type="button" data-toggle="dropdown"
                                        aria-expanded="false">
                                        <i class="fa-solid fa-bars"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        @if (hasPermission('task', 'view'))
                                            <a class="dropdown-item text-info"
                                                href="{{ route('vendor.doctor.slots', $doctor->id) }}"
                                                title="{{ translate('messages.slots') }}"><i
                                                    class="tio-grid"></i>
                                                Slots
                                            </a>
                                        @endif
                                        @if (hasPermission('task', 'edit'))
                                            <a class="dropdown-item text-warning"
                                                href="{{ route('vendor.doctor.edit', $doctor->id) }}"
                                                title="{{ translate('messages.edit') }}"><i class="tio-edit"></i>
                                                Edit
                                            </a>
                                        @endif
                                        @if (hasPermission('task', 'delete'))
                                            <a class="dropdown-item  text-danger form-alert " href="javascript:"
                                                data-id="task-{{ $doctor['id'] }}"
                                                data-message="{{ translate('Want_to_delete_this_task_?') }}"
                                                title="{{ translate('messages.delete_task') }}"><i
                                                    class="tio-delete-outlined"></i>
                                                Delete
                                            </a>
                                            <form action="{{ route('vendor.doctor.delete', $doctor->id) }}" method="post"
                                                id="task-{{ $doctor['id'] }}">
                                                @csrf @method('post')
                                            </form>
                                        @endif
                                    </div>
                                    {{-- <div class="btn--container">
                                        <a href="{{ }}" class="btn btn-sm btn-outline-info"
                                            title="Manage Slots">
                                            <i class="tio-time"></i> Slots
                                        </a>
                                        <a href="{{ }}" class="btn btn-sm btn--warning btn-outline-warning">
                                            <i class="tio-edit"></i>
                                        </a>
                                        <a href="{{ }}"
                                            class="btn btn-sm btn--danger btn-outline-danger form-alert"
                                            data-id="del-{{ $doctor->id }}" data-message="Delete this doctor profile?">
                                            <i class="tio-delete"></i>
                                        </a>
                                        <form id="del-{{ $doctor->id }}"
                                            action="{{ route('vendor.doctor.delete', $doctor->id) }}" method="get">@csrf
                                        </form>
                                    </div> --}}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No doctors added yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $doctors->links() }}</div>
        </div>
    </div>
@endsection
