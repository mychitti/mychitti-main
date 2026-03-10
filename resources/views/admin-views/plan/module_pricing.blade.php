@extends('layouts.admin.app')

@section('title', 'Module Pricing & Durations')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/store.png') }}" class="w--26" alt="">
                </span>
                <span>Module Pricing & Durations</span>
            </h1>
        </div>
        <div class="row">
            {{-- Plan Durations Management --}}
            <div class="col-md-6">
                <div class="card mt-3 ">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="card-title">
                            <span class="card-header-icon mr-1"><i class="tio-time"></i></span>
                            <span>Plan Durations</span>
                        </h5>
                        <button class="btn btn-sm btn--primary" data-toggle="modal" data-target="#addDurationModal">+ Add
                            Duration</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table
                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Label</th>
                                        <th>Months</th>
                                        <th>Sort Order</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($plan_durations as $dur)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $dur->label }}</td>
                                            <td>{{ $dur->months }}</td>
                                            <td>{{ $dur->sort_order }}</td>
                                            <td>
                                                <form action="{{ route('admin.plan.duration.toggle', $dur->id) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn btn-sm {{ $dur->is_active ? 'btn-success' : 'btn-secondary' }}">
                                                        {{ $dur->is_active ? 'Active' : 'Inactive' }}
                                                    </button>
                                                </form>
                                            </td>
                                            <td>
                                                <a class="btn action-btn btn--primary btn-outline-primary"
                                                    data-toggle="modal"
                                                    data-target="#editDurationModal{{ $dur->id }}"><i
                                                        class="tio-edit"></i></a>
                                                <a class="btn action-btn btn--danger btn-outline-danger"
                                                    href="{{ route('admin.plan.duration.delete', $dur->id) }}"
                                                    onclick="return confirm('Delete this duration?')"><i
                                                        class="tio-delete"></i></a>
                                            </td>
                                        </tr>
                                        {{-- Edit Duration Modal --}}
                                        <div class="modal fade" id="editDurationModal{{ $dur->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Duration</h5>
                                                        <button type="button" class="close"
                                                            data-dismiss="modal"><span>&times;</span></button>
                                                    </div>
                                                    <form action="{{ route('admin.plan.duration.update', $dur->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label>Label</label>
                                                                <input class="form-control" type="text" name="label"
                                                                    value="{{ $dur->label }}" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Months</label>
                                                                <input class="form-control" type="number" name="months"
                                                                    value="{{ $dur->months }}" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Sort Order</label>
                                                                <input class="form-control" type="number" name="sort_order"
                                                                    value="{{ $dur->sort_order }}">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn--primary">Update</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Add Duration Modal --}}
            <div class="modal fade" id="addDurationModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Duration</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <form action="{{ route('admin.plan.duration.store') }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Label</label>
                                    <input class="form-control" type="text" name="label" placeholder="e.g. Quarterly"
                                        required>
                                </div>
                                <div class="form-group">
                                    <label>Months</label>
                                    <input class="form-control" type="number" name="months" placeholder="e.g. 3" required>
                                </div>
                                <div class="form-group">
                                    <label>Sort Order</label>
                                    <input class="form-control" type="number" name="sort_order" value="0">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn--primary">Add</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Module Pricing --}}
            <div class="col-md-6">

                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title">
                            <span class="card-header-icon mr-1"><i class="tio-dashboard"></i></span>
                            <span>Module Pricing</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table
                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th>{{ translate('sl') }}</th>
                                        <th>Module</th>
                                        <th>Price</th>
                                        <th>Discounts</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sub_modules as $type)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $type->name }}</td>
                                            <td>{{ _price($type->price_per_month) }}</td>
                                            <td>
                                                @foreach ($plan_durations as $dur)
                                                    <b>{{ $dur->label }}: </b>
                                                    {{ $sub_module_discounts[$type->id]?->firstWhere('plan_duration_id', $dur->id)?->discount ?? 0 }}%<br>
                                                @endforeach
                                            </td>
                                            <td>
                                                <a class="btn action-btn btn--primary btn-outline-primary"
                                                    data-toggle="modal"
                                                    data-target="#editModuleModal{{ $type->id }}"><i
                                                        class="tio-edit"></i></a>
                                            </td>
                                        </tr>
                                        {{-- Edit Module Modal --}}
                                        <div class="modal fade" id="editModuleModal{{ $type->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Module Pricing</h5>
                                                        <button type="button" class="close"
                                                            data-dismiss="modal"><span>&times;</span></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <h4>{{ $type->name }}</h4>
                                                        <form action="{{ route('admin.plan.update-modules') }}"
                                                            method="post">
                                                            @csrf
                                                            <input type="hidden" name="module_id"
                                                                value="{{ $type->id }}">
                                                            <div class="row">
                                                                <div class="form-group col-md-12">
                                                                    <label class="input-label">Name</label>
                                                                    <input class="form-control" type="text"
                                                                        name="name" value="{{ $type->name }}">
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label class="input-label">Price Per Month</label>
                                                                    <input class="form-control" type="number"
                                                                        name="price_per_month"
                                                                        value="{{ $type->price_per_month }}">
                                                                </div>
                                                                @foreach ($plan_durations as $dur)
                                                                    <div class="form-group col-md-6">
                                                                        <label class="input-label">Discount
                                                                            {{ $dur->label }}
                                                                            ({{ $dur->months }}M)</label>
                                                                        <input class="form-control" type="number"
                                                                            step="0.01"
                                                                            name="discounts[{{ $dur->id }}]"
                                                                            value="{{ $sub_module_discounts[$type->id]?->firstWhere('plan_duration_id', $dur->id)?->discount ?? 0 }}">
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                            <div class="btn--container justify-content-end">
                                                                <button type="submit"
                                                                    class="btn btn--primary">{{ translate('messages.submit') }}</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
