@extends('layouts.vendor.app')

@section('title', translate('messages.clients'))

@push('css_or_js')
    <style>
        .dropdown-toggle:not(.dropdown-toggle-empty)::after {
            display: none !important;
        }

        @media (max-width: 500px) {
            .button_cont {
                flex-wrap: wrap;
            }
        }
    </style>
@endpush

@section('content')

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header flex-wrap d-flex justify-content-between w-100">
            <h1 class="page-header-title">

                <span>
                    {{ translate('messages.client_list') }} <span class="badge badge-soft-dark ml-2"
                        id="itemCount">{{ $customers->total() }}</span>
                </span>
            </h1>
            <div class="d-flex button_cont align-items-center gap-2 justify-content-end">
                @if (hasPermission('client_manage', 'delete'))
                    <!-- More Options Dropdown -->
                    <!-- Delete Button -->
                    <div class="mr-1 delete_selected_btn" style="display:none;">
                        <button style=" white-space: nowrap;" id="delete_all" class="btn btn-sm btn-outline-danger px-3 py-2"
                            title="Delete Selected">
                            <i class="tio-delete"></i> Delete Selected
                        </button>
                    </div>
                    <!-- Delete Button -->

                    <!-- Select All -->

                    <div class="form-check mr-1">
                        <input type="checkbox" class="form-check-input" id="check_all">
                        <label style=" white-space: nowrap;" class="form-check-label" id="check_all_label"
                            for="check_all">Select All</label>
                    </div>
                @endif

                <div class="d-flex flex-wrap gap-1">
                    <div class="mr-1 d-flex gap-1 flex-wrap">
                        <form action="">
                            <select class="form-control mx-1" name="type" onchange="this.form.submit()">
                                <option {{ request('type' ?? '') == 'all' ? 'selected' : '' }} value="all">All</option>
                                <option {{ request('type' ?? '') == 'customer' ? 'selected' : '' }} value="customer">
                                    Customer</option>
                                <option {{ request('type' ?? '') == 'vendor' ? 'selected' : '' }} value="vendor">Vendor
                                </option>
                            </select>
                        </form>
                        @if (hasPermission('client_manage', 'add'))
                            <button style=" white-space: nowrap;"
                                class="ml-1 btn btn-sm btn-primary customer_modal_btn modal_btn mb-0" data-value="customer"
                                data-toggle="modal" data-target="#customerAddModal">
                                + Add Customer
                            </button>
                            <a style=" white-space: nowrap;" class="btn btn-sm btn-primary  modal_btn mb-0"
                                data-value="vendor" data-toggle="modal" data-target="#customerAddModal">
                                + Add Vendor
                            </a>
                        @endif
                         <div class="dropdown mr-1">
                        <button class="btn btn-info  btn-sm dropdown-toggle px-3 py-2" type="button" data-toggle="dropdown"
                            aria-expanded="false">
                            More Options
                        </button>
                        <div class="dropdown-menu">
                            @if (hasPermission('client_manage', 'import'))
                                <a class="dropdown-item text-success modal_btn" data-toggle="modal"
                                    data-target="#customerImportModal">
                                    <i class="tio-download-to"></i> Import Client List
                                </a>
                            @endif
                            @if (hasPermission('client_manage', 'export'))
                                <a class="dropdown-item text-warning" href="{{ route('vendor.customer.export') }}">
                                    <i class="tio-upload-outlined"></i> Export Client List
                                </a>
                            @endif

                        </div>
                    </div>

                    </div>

                   
                </div>
                <!-- Search Input -->
                <form action="" class="input-group" style="max-width: 270px;">
                    <input type="text" value="{{ $search ?? '' }}" name="search" class="form-control"
                        placeholder="Search By Phone or Name">
                    <div class="input-group-append">
                        <button class="btn btn-secondary" type="submit">
                            <i class="tio-search"></i>
                        </button>
                    </div>
                </form>

            </div>

        </div>
        @if (hasPermission('client_manage', 'add'))
            <div class="modal fade" id="customerAddModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">

                            <h5 class="modal-title" id="exampleModalLabel">Add New <span id="user_ytp">Customer</span></h5>

                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            @include('vendor-views/forms/customer_add')
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @if (hasPermission('client_manage', 'import'))
            <div class="modal fade" id="customerImportModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Import Client List</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('vendor.customer.upload-excel') }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                <a href="{{ asset('storage/app/public/util/example-import.xlsx') }}" download
                                    class="btn btn-outline-primary mb-2">View Example</a>
                                <div class="form-group">
                                    <label for="file">Upload Excel File</label>
                                    <input type="file" name="file" class="form-control" id="file"
                                        accept=".xlsx,.xls">
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="importSendWelcome" name="send_welcome" value="1">
                                        <label class="custom-control-label" for="importSendWelcome" style="font-size:13px;">
                                            Send a WhatsApp welcome message to the newly imported customers
                                            <small class="text-muted d-block">Sent in the background from your connected WhatsApp number using your approved welcome template.</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group w-100 ">
                                    <button type="submit" class="btn btn-primary float-right">Import</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <!-- End Page Header -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header py-2 border-0">

                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive datatable-custom">
                            <table id="columnSearchDatatable" style="min-height: 150px;"
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
                                        @if (hasPermission('client_manage', 'delete'))
                                            <th class=" border-0 text-center"></th>
                                        @endif
                                        <th class=" border-0 text-center">{{ translate('messages.#') }}</th>
                                        <th class="w-33p border-0 text-center">{{ translate('messages.Type') }}</th>
                                        <th class="w-33p border-0 ">{{ translate('messages.Name') }}</th>
                                        <th class="w-33p border-0 text-center">{{ translate('messages.Phone') }}</th>
                                        <th class="w-33p border-0 text-center">{{ translate('messages.Email') }}</th>
                                        <th class="w-33p border-0 text-center">Action</th>
                                    </tr>
                                </thead>

                                <tbody id="table-div">
                                    @foreach ($customers as $key => $customer)
                                        <tr>
                                            @if (hasPermission('client_manage', 'delete'))
                                                <td class="text-center"> <input type="checkbox" name="client_id[]"
                                                        value="{{ $customer->id }}" name="" class="check_select "
                                                        id="">
                                                </td>
                                            @endif
                                            <td class="text-center">{{ $key + $customers->firstItem() }}</td>
                                            <td class="text-center">
                                                <span
                                                    class="badge badge-soft-{{ $customer->user_type == 'customer' ? 'info' : 'warning' }} badge-pill ml-1">
                                                    {{ ucfirst($customer->user_type) }}
                                                </span>
                                            </td>
                                            <td class="">
                                                <img style="width: 30px; aspect-ratio:1;" class="onerror-image"
                                                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($customer['profile_pic'], asset('storage/app/public/profile/') . '/' . $customer['profile_pic'], asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                                    alt="{{ $customer['f_name'] }}">

                                                <a class="text-dark fw-bold"
                                                    href="{{hasPermission('client_manage', 'view') ? route('vendor.customer.view', [$customer['id']]) : '#'}}">{{ $customer->f_name }}</a>
                                            </td>
                                            <td class="text-center">{{ $customer->phone }}</td>
                                            <td class="text-center">{{ $customer->email }}</td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn p-1 dropdown-toggle" type="button"
                                                        data-toggle="dropdown" aria-expanded="false">
                                                        <img style="    width: 24px; filter: contrast(0);"
                                                            src = "{{ asset('storage/app/public/util/10025520.png') }}"
                                                            alt="action">
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        @if (hasPermission('client_manage', 'view'))
                                                            <a class="dropdown-item text-info"
                                                                href="{{ route('vendor.customer.view', [$customer['id']]) }}"><i
                                                                    class="tio-visible"></i> View</a>
                                                        @endif
                                                        @if (hasPermission('client_manage', 'comment'))
                                                            <a type="button" class="dropdown-item text-warning"
                                                                data-whatever="{{ $customer['id'] }}" data-toggle="modal"
                                                                data-target="#clientCommentModal"><i class="tio-edit"></i>
                                                                Comment</a>
                                                        @endif
                                                        @if (hasPermission('client_manage', 'edit'))
                                                            <a class="dropdown-item text-info"
                                                                href="{{ route('vendor.customer.edit', [$customer['id']]) }}"><i
                                                                    class="tio-edit"></i> Edit</a>
                                                        @endif
                                                        @if (hasPermission('client_manage', 'delete'))
                                                            <a class="dropdown-item form-alert text-danger"
                                                                href="javascript:;"
                                                                data-id="customer-{{ $customer['id'] }}"
                                                                data-message="{{ translate('messages.Want to delete this customer') }}"
                                                                title="{{ translate('messages.delete_customer') }}"><i
                                                                    class="tio-delete-outlined"></i>
                                                                Delete</a>
                                                        @endif
                                                        @if (hasPermission('billing', 'add_basic'))
                                                            <a class="dropdown-item text-success" href="#"><i
                                                                    class="tio-add-square-outlined"></i> Invoice</a>
                                                        @endif
                                                    </div>
                                                    @if (hasPermission('client_manage', 'delete'))
                                                        <form
                                                            action="{{ route('vendor.customer.delete', [$customer['id']]) }}"
                                                            method="post" id="customer-{{ $customer['id'] }}">
                                                            @csrf @method('get')
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if (hasPermission('client_manage', 'comment'))
                                        <div class="modal fade" id="clientCommentModal" tabindex="-1"
                                            aria-labelledby="exampleModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="exampleModalLabel">Add Comment</h5>
                                                        <button type="button" class="close" data-dismiss="modal"
                                                            aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form action="{{ route('vendor.customer.comment-save') }}"
                                                            method="post">
                                                            @csrf
                                                            <input type="hidden" name="user_id" id="user_id">
                                                            <div class="form-group">
                                                                <label for="exampleInputEmail1">Comment</label>
                                                                <textarea placeholder="Start typing..." required name="comment" class="form-control" id=""></textarea>
                                                            </div>
                                                            <div class="d-flex w-100 justify-content-end">
                                                                <button type="submit"
                                                                    class="btn btn-primary">Save</button>
                                                            </div>
                                                        </form>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>



                    <div class="card-footer page-area">
                        <!-- Pagination -->
                        <div class="page-area">
                            {!! $customers->links() !!}
                        </div>
                        <!-- Pagination -->
                        @if (count($customers) === 0)
                            @if ($search)
                                <h3> No Results Found For "{{ $search }}"</h3>
                            @else
                                <div class="empty--data">
                                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                        alt="public">
                                    <h5>
                                        {{ translate('no_data_found') }}


                                    </h5>
                                    @if (hasPermission('client_manage', 'add'))
                                        <button class="btn btn-sm btn-outline-primary" data-toggle="modal"
                                            data-target="#customerAddModal"><i class="tio-add-square-outlined"></i> Start
                                            Adding Your Customers</button>
                                    @endif
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- invoice start  -->


    <!-- invoice start end -->

@endsection
@push('script_2')
    @include('vendor-views/js/custom-buttons-js')

    <script>
        @if ($tab == 'add')
            $(".customer_modal_btn").click()
        @endif

        // When clicking select all
        $(document).on('change', '#check_all', function() {
            $('.check_select').prop('checked', this.checked);
            if ($(this).prop('checked') == true) {
                $('#check_all_label').text('Deselect All');
                $('.delete_selected_btn').show();
                $('#check_all').prop('checked', true);

            } else {
                $('#check_all_label').text('Select All');
                $('.delete_selected_btn').hide();
            }
        });

        // When individual checkbox is clicked
        $(document).on('change', '.check_select', function() {
            let total = $('.check_select').length;
            let checked = $('.check_select:checked').length;

            $('#check_all').prop('checked', total === checked);

            if (checked > 0) {
                //$('#check_all_label').text('Deselect All');
                // $('#check_all').prop('checked', true);
                $('.delete_selected_btn').show();
            } else {
                $('#check_all_label').text('Select All');
                $('.delete_selected_btn').hide();
            }
        });


        $("#delete_all").on('click', function() {

            Swal.fire({
                title: '{{ translate('messages.Are you sure?') }}',
                text: 'You want to delete selected clients',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    var selectedIds = [];
                    $('.check_select:checked').each(function() {
                        selectedIds.push($(this).val());
                    });
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.post({
                        url: '{{ route('vendor.customer.bulk-delete') }}',
                        data: {
                            client_ids: selectedIds
                        },
                        success: function(data) {
                            window.location.reload();
                        }
                    });
                }
            })

        })
        $(document).ready(function() {
            $('.modal_btn').on('click', function() {
                console.log("fsasfasdfd")
                $('#add_user_type').val($(this).attr('data-value'));
                $("#user_ytp").text($(this).attr('data-value'))
            });
        });
        $('#clientCommentModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            var id = button.data('whatever')
            var modal = $(this)
            modal.find('.modal-body #user_id').val(id)
        })
    </script>
@endpush
