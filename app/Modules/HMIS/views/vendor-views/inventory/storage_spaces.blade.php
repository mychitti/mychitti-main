@extends('layouts.vendor.app')

@section('title', 'Storage Units')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Storage Units</h1>
            <div class="page-header-select-wrapper">

            </div>
        </div>
        <!-- End Page Header -->
        @if (hasPermission('inventory_storage_units', 'add'))

            <div class="">
                <div class="card h-100">
                    <h3 class="mx-3 mt-2 mb-0">Add Unit</h3>
                    <div class="card-body row pt-1 d-flex flex-column">
                        <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class=" btn btn_sm nav-link active" id="pills-room-tab" data-toggle="pill"
                                    data-target="#pills-room" type="button" role="tab" aria-controls="pills-room"
                                    aria-selected="true">Room</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class=" btn btn_sm nav-link" id="pills-stand-tab" data-toggle="pill"
                                    data-target="#pills-stand" type="button" role="tab" aria-controls="pills-stand"
                                    aria-selected="false">Stand</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class=" btn btn_sm nav-link" id="pills-shelf-tab" data-toggle="pill"
                                    data-target="#pills-shelf" type="button" role="tab" aria-controls="pills-shelf"
                                    aria-selected="false">Shelf</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class=" btn btn_sm nav-link" id="pills-box-tab" data-toggle="pill"
                                    data-target="#pills-box" type="button" role="tab" aria-controls="pills-box"
                                    aria-selected="false">Box /
                                    Container</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="pills-tabContent">
                            <div class="tab-pane fade show active" id="pills-room" role="tabpanel"
                                aria-labelledby="pills-room-tab">
                                <form enctype="multipart/form-data" class="w-100"
                                    action="{{ route('vendor.inventory.storage-unit.store') }}" method="post">
                                    @csrf
                                    <input type="hidden" name="unit_type" value="room">
                                    <div class="form-row col-md-4">
                                        <label for="exampleInputEmail1">Room Name / Room Number<span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="name" required placeholder="EX: 101"
                                            class="form-control">
                                    </div>
                                    <div class="form-row col-12">
                                        <div class="col my-2">
                                            <button class="btn  btn--primary btn-outline-primary">Save</button>
                                        </div>
                                    </div>
                                </form> 
                            </div>
                            {{-- ========================== STAND =================================== --}}
                            <div class="tab-pane fade" id="pills-stand" role="tabpanel" aria-labelledby="pills-stand-tab">
                                <form enctype="multipart/form-data" class="w-100"
                                    action="{{ route('vendor.inventory.storage-unit.store') }}" method="post">
                                    @csrf
                                    <input type="hidden" name="unit_type" value="stand">
                                    <div class="form-row col-md-4">
                                        <label for="exampleInputEmail1">Name / Number<span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="name" required placeholder="Ex: ST91"
                                            class="form-control">
                                    </div>

                                    <div class="form-row col-md-4">
                                        <label for="exampleInputEmail1">Room</label>
                                        <select required name="parent_id" id=""
                                            class="form-control js-select2-custom get-request">
                                            <option value="">---{{ translate('messages.select') }}---</option>
                                            @foreach ($storage_rooms as $key => $room)
                                                <option value="{{ $room->id }}">{{ $room->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>


                                    <div class="form-row col-12">
                                        <div class="col my-2">
                                            <button class="btn  btn--primary btn-outline-primary">Save</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            {{-- ========================== STAND END=================================== --}}


                            {{-- ========================== SHELF =================================== --}}
                            <div class="tab-pane fade" id="pills-shelf" role="tabpanel"
                                aria-labelledby="pills-shelf-tab">
                                <form enctype="multipart/form-data" class="w-100"
                                    action="{{ route('vendor.inventory.storage-unit.store') }}" method="post">
                                    @csrf
                                    <input type="hidden" name="unit_type" value="shelf">
                                    <div class="form-row col-md-4">
                                        <label for="exampleInputEmail1">Name / Number<span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="name" required placeholder="Ex: 93"
                                            class="form-control">
                                    </div>

                                    <div class="form-row col-md-4">
                                        <label for="exampleInputEmail1">Stand</label>
                                        <select required name="parent_id" id=""
                                            class="form-control js-select2-custom get-request">
                                            <option value="">---{{ translate('messages.select') }}---</option>
                                            @foreach ($storage_stands as $key => $unit)
                                                <option value="{{ $unit->id }}">
                                                    {{ $unit->parent ? $unit->parent->name . ' > ' : '' }}
                                                    {{ $unit->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-row col-12">
                                        <div class="col my-2">
                                            <button class="btn  btn--primary btn-outline-primary">Save</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            {{-- ========================== SHELF END=================================== --}}


                            {{-- ========================== BOX =================================== --}}
                            <div class="tab-pane fade" id="pills-box" role="tabpanel" aria-labelledby="pills-box-tab">
                                <form enctype="multipart/form-data" class="w-100"
                                    action="{{ route('vendor.inventory.storage-unit.store') }}" method="post">
                                    @csrf
                                    <input type="hidden" name="unit_type" value="container">
                                    <div class="form-row col-md-4">
                                        <label for="exampleInputEmail1">Name / Number<span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="name" required placeholder="Ex: BX3"
                                            class="form-control">
                                    </div>

                                    <div class="form-row col-md-4">
                                        <label for="exampleInputEmail1">Shelf</label>
                                        <select required name="parent_id" id=""
                                            class="form-control js-select2-custom get-request">
                                            <option value="">---{{ translate('messages.select') }}---</option>
                                            @foreach ($storage_shelves as $key => $unit)
                                                <option value="{{ $unit->id }}">
                                                    {{ $unit->parent ? $unit->parent->name . ' > ' : '' }}
                                                    {{ $unit->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-row col-12">
                                        <div class="col my-2">
                                            <button class="btn  btn--primary btn-outline-primary">Save</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            {{-- ========================== BOX END=================================== --}}
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @if (hasPermission('inventory_storage_units', 'list'))

            <div class="row g-2">
                <div class="col-md-12 px-0">
                    <div class="card h-100">
                        <h3 class="mx-3 mt-2 mb-0">Room List</h3>
                        <div class="table-responsive datatable-custom" id="table-div">
                            <table id="datatable"
                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('sl') }}</th>
                                        <th class="border-0">Room Name / Number</th>
                                        <th class="border-0">Total Stands</th>
                                        <th class="border-0 ">Created At</th>
                                        <th class="border-0 ">Action</th>
                                    </tr>
                                </thead>

                                <tbody id="set-rows">
                                    @foreach ($storage_rooms as $key => $unit)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                {{ $unit->name }}
                                            </td>
                                            <td>
                                                {{ $unit->children->count() }}
                                            </td>
                                            <td>
                                                {{ $unit->created_at }}
                                            </td>
                                            <td>
                                                <div class="btn--container justify-content-center">
                                                    @if (hasPermission('inventory_storage_units', 'edit'))
                                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                                            data-id="{{ $unit->id }}"
                                                            data-value="{{ $unit->name }}" data-type="room"
                                                            type="button" data-toggle="modal"
                                                            data-target="#editUnitModal"><i class="tio-edit"></i>
                                                        </a>
                                                    @endif
                                                    @if (hasPermission('inventory_storage_units', 'delete'))
                                                        <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                            href="javascript:" data-id="vendor-{{ $unit['id'] }}"
                                                            data-message="{{ translate('If you want to remove this room?.. All its stands, shelves and boxes also will be deleted.') }}"
                                                            title="{{ translate('messages.delete_storage_unit') }}"><i
                                                                class="tio-delete-outlined"></i>
                                                        </a>
                                                        <form
                                                            action="{{ route('vendor.inventory.storage-unit.delete', [$unit['id']]) }}"
                                                            method="post" id="vendor-{{ $unit['id'] }}">
                                                            @csrf @method('post')
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            @if (count($storage_rooms) === 0)
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
            <div class="row g-2">
                <div class="col-md-12 px-0">
                    <div class="card h-100">
                        <h3 class="mx-3 mt-2 mb-0">Stand List</h3>
                        <div class="table-responsive datatable-custom" id="table-div">
                            <table id="datatable"
                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('sl') }}</th>
                                        <th class="border-0">Stand Name / Number</th>
                                        <th class="border-0">Total Shelves</th>
                                        <th class="border-0 ">Created At</th>
                                        <th class="border-0 ">Action</th>
                                    </tr>
                                </thead>

                                <tbody id="set-rows">
                                    @foreach ($storage_stands as $key => $unit)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                {{ $unit->name }}
                                            </td>
                                            <td>
                                                {{ $unit->children->count() }}
                                            </td>
                                            <td>
                                                {{ $unit->created_at }}
                                            </td>
                                            <td>
                                                <div class="btn--container justify-content-center">
                                                    @if (hasPermission('inventory_storage_units', 'edit'))
                                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                                            data-id="{{ $unit->id }}"
                                                            data-value="{{ $unit->name }}" data-type="room"
                                                            type="button" data-toggle="modal"
                                                            data-target="#editUnitModal"><i class="tio-edit"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            @if (count($storage_stands) === 0)
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
            <div class="row g-2">
                <div class="col-md-12 px-0">
                    <div class="card h-100">
                        <h3 class="mx-3 mt-2 mb-0">Shelf List</h3>
                        <div class="table-responsive datatable-custom" id="table-div">
                            <table id="datatable"
                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('sl') }}</th>
                                        <th class="border-0">Shelf Name / Number</th>
                                        <th class="border-0">Total Box</th>
                                        <th class="border-0 ">Created At</th>
                                        <th class="border-0 ">Action</th>
                                    </tr>
                                </thead>

                                <tbody id="set-rows">
                                    @foreach ($storage_shelves as $key => $unit)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                {{ $unit->name }}
                                            </td>
                                            <td>
                                                {{ $unit->children->count() }}
                                            </td>
                                            <td>
                                                {{ $unit->created_at }}
                                            </td>
                                            <td>
                                                <div class="btn--container justify-content-center">
                                                    @if (hasPermission('inventory_storage_units', 'edit'))
                                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                                            data-id="{{ $unit->id }}"
                                                            data-value="{{ $unit->name }}" data-type="room"
                                                            type="button" data-toggle="modal"
                                                            data-target="#editUnitModal"><i class="tio-edit"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            @if (count($storage_shelves) === 0)
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
            <div class="row g-2">
                <div class="col-md-12 px-0">
                    <div class="card h-100">
                        <h3 class="mx-3 mt-2 mb-0">Box List</h3>
                        <div class="table-responsive datatable-custom" id="table-div">
                            <table id="datatable"
                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('sl') }}</th>
                                        <th class="border-0">Box Name / Number</th>
                                        <th class="border-0 ">Created At</th>
                                        <th class="border-0 ">Action</th>
                                    </tr>
                                </thead>

                                <tbody id="set-rows">
                                    @foreach ($storage_containers as $key => $unit)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                {{ $unit->name }}
                                            </td>
                                            <td>
                                                {{ $unit->created_at }}
                                            </td>
                                            <td>
                                                <div class="btn--container justify-content-center">
                                                    @if (hasPermission('inventory_storage_units', 'edit'))
                                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                                            data-id="{{ $unit->id }}"
                                                            data-value="{{ $unit->name }}" data-type="room"
                                                            type="button" data-toggle="modal"
                                                            data-target="#editUnitModal"><i class="tio-edit"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            @if (count($storage_containers) === 0)
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
    @if (hasPermission('inventory_storage_units', 'edit'))
        <div class="modal fade" id="editUnitModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Edit Storage Unit</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form enctype="multipart/form-data" class="w-100"
                        action="{{ route('vendor.inventory.storage-unit.update') }}" method="post">
                        @csrf
                        <input type="hidden" name="unit_id" id="edit_unit_id">
                        <input type="hidden" name="unit_type" id="edit_unit_type">
                        <div class="modal-body">
                            <div class="form-row ">
                                <label for="exampleInputEmail1">Name / Number</label>
                                <input type="text" id="edit_unit_name" name="name" required placeholder="EX : 28"
                                    class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    @endif

@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>

    <script>
        $('#editUnitModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var unitId = button.data('id'); // Extract info from data-* attributes
            var unitType = button.data('type');
            var unitName = button.data('value');

            var modal = $(this);
            modal.find('#edit_unit_id').val(unitId);
            modal.find('#edit_unit_type').val(unitType);
            modal.find('#edit_unit_name').val(unitName);
        });
    </script>
@endpush
