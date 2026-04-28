@extends('layouts.vendor.app')
@section('title', 'Patient List')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h1 class="page-header-title mb-2">
                <span class="page-header-icon">
                    <i class="tio-user-add" style="font-size:22px;"></i>
                </span>
                <span>
                    Patients
                    <span class="badge badge-soft-dark ml-2">{{ $patients->total() }}</span>
                </span>
            </h1>
            <div class="d-flex gap-2 mb-2">
                <a href="{{ route('vendor.patient.export') }}" class="btn  btn-outline-success">
                    <i class="tio-download"></i> Export
                </a>
                <a href="{{ route('vendor.patient.add') }}" class="btn btn--primary">
                    <i class="tio-add-circle"></i> Add New Patient
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-2 border-0">
            <form class="search-form ml-auto">
                <div class="input-group input--group">
                    <input value="{{ $search }}" type="search" name="search" class="form-control"
                        placeholder="Search by name, phone, UID...">
                    <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>MUID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Gender</th>
                        <th>Blood Group</th>
                        <th>Age</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $p)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><span class="badge badge-soft-info">{{ $p->patient_uid }}</span></td>
                        <td>{{ $p->name }}</td>
                        <td>{{ $p->phone ?? '—' }}</td>
                        <td>{{ ucfirst($p->gender ?? '—') }}</td>
                        <td>{{ $p->blood_group ?? '—' }}</td>
                        <td>
                            @if($p->dob)
                                {{ \Carbon\Carbon::parse($p->dob)->age }} yrs
                            @else —
                            @endif
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn p-1 dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-bars"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item text-primary" href="{{ route('vendor.patient.show', $p->id) }}">
                                        <i class="tio-visible mr-2"></i> View
                                    </a>
                                    <a class="dropdown-item text-warning" href="{{ route('vendor.patient.edit', $p->id) }}">
                                        <i class="tio-edit mr-2"></i> Edit
                                    </a>
                                    <a class="dropdown-item text-danger"
                                        href="{{ route('vendor.patient.delete', $p->id) }}"
                                        onclick="return confirm('Delete this patient?')">
                                        <i class="tio-delete mr-2"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">No patients found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $patients->appends(['search' => $search])->links() }}
        </div>
    </div>
</div>
@endsection
