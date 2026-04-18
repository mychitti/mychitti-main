@extends('layouts.vendor.app')
@section('title', 'Nurses')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h1 class="page-header-title mb-2">
                <span class="page-header-icon"><i class="tio-user-outlined" style="font-size:22px;"></i></span>
                Nurses <span class="badge badge-soft-dark ml-2">{{ $nurses->total() }}</span>
            </h1>
            <div class="d-flex gap-2 mb-2">
                <a href="{{ route('vendor.nurse.export') }}" class="btn  btn-outline-success">
                    <i class="tio-download"></i> Export
                </a>
                <a href="{{ route('vendor.nurse.create') }}" class="btn btn--primary">
                    <i class="tio-add-circle"></i> Add Nurse
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Qualification</th>
                        <th>Department</th>
                        <th>Ward</th>
                        <th>Shift</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($nurses as $key => $nurse)
                    <tr>
                        <td>{{ $nurses->firstItem() + $key }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($nurse->employee?->image)
                                    <img class="avatar avatar-sm mr-2 rounded-circle"
                                        src="{{ asset('storage/vendor') . '/' . $nurse->employee->image }}"
                                        alt="">
                                @else
                                    <span class="avatar avatar-sm avatar-soft-success avatar-circle mr-2">
                                        <span class="avatar-initials">{{ strtoupper(substr($nurse->employee?->f_name ?? 'N', 0, 1)) }}</span>
                                    </span>
                                @endif
                                <div>
                                    <span class="d-block font-weight-bold">
                                        {{ $nurse->employee?->f_name }} {{ $nurse->employee?->l_name }}
                                    </span>
                                    <small class="text-muted">{{ $nurse->employee?->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $nurse->qualification ?: '—' }}</td>
                        <td>{{ $nurse->department ?: '—' }}</td>
                        <td>{{ $nurse->ward?->ward_name ?: '—' }}</td>
                        <td>
                            <span class="badge badge-soft-info">
                                {{ \App\Models\NurseProfile::SHIFTS[$nurse->shift] ?? ucfirst($nurse->shift) }}
                            </span>
                        </td>
                        <td>
                            <button class="btn p-1 dropdown-toggle" type="button" data-toggle="dropdown">
                                <i class="fa-solid fa-bars"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item"
                                   href="{{ route('vendor.nurse.show', $nurse->id) }}">
                                    <i class="tio-visible"></i> View
                                </a>
                                <a class="dropdown-item text-warning"
                                   href="{{ route('vendor.nurse.edit', $nurse->id) }}">
                                    <i class="tio-edit"></i> Edit
                                </a>
                                <a class="dropdown-item text-danger form-alert" href="javascript:"
                                   data-id="nurse-{{ $nurse->id }}"
                                   data-message="Delete this nurse profile?">
                                    <i class="tio-delete-outlined"></i> Delete
                                </a>
                                <form action="{{ route('vendor.nurse.delete', $nurse->id) }}"
                                      method="post" id="nurse-{{ $nurse->id }}">
                                    @csrf
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No nurses added yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $nurses->links() }}</div>
    </div>
</div>
@endsection
