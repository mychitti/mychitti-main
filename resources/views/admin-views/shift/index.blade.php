@extends('layouts.admin.app')

@section('title', translate('messages.shifts'))

@push('css_or_js')
    <style>
        #countent {
            padding-top: 19px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header flex-wrap w-100 d-flex justify-content-between">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <i class="tio-timer"></i>
                </span>
                <span>
                    {{ translate('messages.shifts') }} <span class="badge badge-soft-dark ml-2"
                        id="itemCount">{{ $shifts->total() }}</span>
                </span>
            </h1>
            @if (hasPermission('shift_manage', 'add'))
                <button class="btn btn_sm btn--primary" data-toggle="modal" data-target="#addShiftModal">+ Add New
                    Shift</button>
            @endif
        </div>
        <!-- End Page Header -->
        @if (hasPermission('shift_manage', 'list'))

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header py-2 border-0">
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive datatable-custom">
                                <table id="columnSearchDatatable"
                                    class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                                    data-hs-datatables-options='{
                                    "search": "#datatableSearch",
                                    "entries": "#datatableEntries",
                                    "isResponsive": false,
                                    "isShowPaging": false,
                                    "paging":false,
                                }'>
                                    <thead class="thead-light">
                                        <tr>
                                            <th class=" border-0 text-center">{{ translate('messages.#') }}</th>
                                            <th class=" border-0 text-center">{{ translate('messages.name') }}</th>
                                            <th class=" border-0 text-center">{{ translate('messages.start_time') }}</th>
                                            <th class=" border-0 text-center">{{ translate('messages.end_time') }}</th>
                                            <th class=" border-0 text-center">{{ translate('messages.action') }}</th>
                                        </tr>
                                    </thead>

                                    <tbody id="table-div">
                                        @foreach ($shifts as $key => $shift)
                                            <tr>
                                                <td class="text-center">{{ $key + $shifts->firstItem() }}</td>
                                                <td class="text-center">{{ $shift->name }}</td>
                                                <td class="text-center">{{ $shift->start_time }}</td>
                                                <td class="text-center">{{ $shift->end_time }}</td>
                                                <td class="text-center">
                                                    <div class="btn--container justify-content-center">
                                                        @if (hasPermission('shift_manage', 'edit'))
                                                            <a class="btn action-btn btn--primary btn-outline-primary edit_btn"
                                                                data-sttime="{{ $shift['start_time'] }}"
                                                                data-endtime="{{ $shift['end_time'] }}"
                                                                data-id="{{ $shift['id'] }}"
                                                                data-name="{{ $shift['name'] }}" data-toggle="modal"
                                                                data-target="#editAdModal"
                                                                title="{{ translate('messages.edit_ad') }}"><i
                                                                    class="tio-edit"></i>
                                                            </a>
                                                        @endif
                                                        @if (hasPermission('shift_manage', 'delete'))
                                                            <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                                href="javascript:" data-id="category-{{ $shift['id'] }}"
                                                                data-message="{{ translate('Want to delete this shift') }}"
                                                                title="{{ translate('messages.delete_shift') }}"><i
                                                                    class="tio-delete-outlined"></i>
                                                            </a>
                                                            <form
                                                                action="{{ route('admin.shifts.delete', [$shift['id']]) }}"
                                                                method="get" id="category-{{ $shift['id'] }}">
                                                                @csrf @method('get')
                                                            </form>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer page-area">
                            <!-- Pagination -->
                            {!! $shifts->links() !!}
                            <!-- Pagination -->
                            @if (count($shifts) === 0)
                                <div class="empty--data">
                                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                        alt="public">
                                    <h5>
                                        {{ translate('no_data_found') }}
                                    </h5>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    @if (hasPermission('shift_manage', 'edit'))
        <div class="modal fade" id="editAdModal" data-backdrop="static" data-keyboard="false" tabindex="-1"
            aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel">Edit Shift</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" class="row" action="{{ route('admin.shifts.update') }}">
                            @csrf
                            <input type="hidden" name="shift_id" class="edit_id">
                            <div class="col-12">
                                <label>Name</label>
                                <input class="form-control edit_name" type="text" name="name">
                            </div>
                            <div class="col-md-6">
                                <label>Start Time</label>
                                <input class="form-control edit_start_time" type="time" name="start_time">
                            </div>
                            <div class="col-md-6">
                                <label>End Time</label>
                                <input class="form-control edit_end_time" type="time" name="end_time">
                            </div>
                            <div class="d-flex w-100 justify-content-end mt-2">
                                <button type="submit" class="btn btn--primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if (hasPermission('shift_manage', 'add'))
        <div class="modal fade" id="addShiftModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Add New Shift</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" class="row" action="{{ route('admin.shifts.store') }}">
                            @csrf
                            <div class="col-12">

                                <label>Name</label>
                                <input class="form-control" type="text" name="name"
                                    value="{{ old('name', $shift->name ?? '') }}">
                            </div>

                            <div class="col-md-6">
                                <label>Start Time</label>
                                <input class="form-control" type="time" name="start_time"
                                    value="{{ old('start_time', $shift->start_time ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label>End Time</label>
                                <input class="form-control" type="time" name="end_time"
                                    value="{{ old('end_time', $shift->end_time ?? '') }}">
                            </div>
                            <div class="d-flex w-100 justify-content-end mt-2">
                                <button type="submit" class="btn btn--primary">Create</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('script_2')
    <script>
        $(".edit_btn").on('click', function() {
            var id = $(this).attr('data-id')
            var ad_name = $(this).attr('data-name')
            var start_time = $(this).attr('data-sttime')
            var end_time = $(this).attr('data-endtime')

            $(".edit_id").val(id)
            $(".edit_name").val(ad_name)
            $(".edit_start_time").val(start_time)
            $(".edit_end_time").val(end_time)
        })
    </script>
@endpush
