@extends('layouts.vendor.app')

@section('title', 'Restaurant Tables')

@section('content')
    
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header" style="display: flex;justify-content: space-between;">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Restaurant Tables <span
                    class="badge badge-soft-dark ml-2" id="itemCount">{{ count($tables) }}</span></h1>
            <div class="page-header-select-wrapper">
                <a href="{{ route('vendor.pos.restaurant-tables.create') }}" class="btn btn-primary btn-sm float-right">
                    Add Table
                </a>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Restaurant Tables</h5>
                    <form action="" id="search-form" class="search-form">
                        <!-- Search -->
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" value="{{request()->search ?? ''}}" name="search" class="form-control"
                                placeholder="Search Table" aria-label="{{ translate('messages.search') }}" required>
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable"
                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                        data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false

                        }'>
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Table Name</th>
                                <th>Capacity</th>
                                {{-- <th>Room Type</th> --}}
                                <th>Status</th>
                                <th width="120">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tables as $key => $t)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $t->name }}</td>
                                    <td>{{ $t->capacity }}</td>
                                    {{-- <td>
                                        {{ $t->room_type == 'ac' ? 'AC' : 'Non AC' }}
                                    </td> --}}
                                    <td>
                                        <span
                                            class="badge badge-{{ $t->status == 'free' ? 'success' : ($t->status == 'occupied' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($t->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <a class="btn action-btn btn--primary btn-outline-primary"
                                                href="{{ route('vendor.pos.restaurant-tables.edit', $t->id) }}"
                                                title="{{ translate('messages.edit_ad') }}"><i class="tio-edit"></i>
                                            </a>
                                            <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                href="javascript:" data-id="category-{{ $t['id'] }}"
                                                data-message="{{ translate('Want to delete this table') }}"
                                                title="{{ translate('messages.delete_table') }}"><i
                                                    class="tio-delete-outlined"></i>
                                            </a>
                                            <form action="{{ route('vendor.pos.restaurant-tables.destroy', $t->id) }}"
                                                method="get" id="category-{{ $t['id'] }}">
                                                @csrf @method('DELETE')

                                            </form>
                                        </div>

                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>

                </div>
            </div>
        </div>

    @endsection
