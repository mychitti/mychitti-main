@extends('layouts.admin.app')

@section('title', translate('Customer List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title mr-3">
                <span class="page-header-icon">
                    <img src="{{ asset('/public/assets/admin/img/people.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ translate('messages.customers') }} <span class="badge badge-soft-dark ml-2"
                        id="count">{{ $customers->total() }}</span>
                </span>
                <div class="d-flex" style="position: absolute; right: 30px;">
                    <button class="btn btn-primary mx-2" data-toggle="modal" data-target="#exampleModal">Import
                        Excel</button>
                    <a href="{{ route('admin.users.customer.add-new') }}" class="btn btn-primary">+ Add Customer</a>
                </div>
                <!-- Modal -->
                <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Upload File</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                            <a href="{{asset('storage/app/public/uploaded/excel')}}/users.xlsx" class="btn btn-primary btn-outline-primary mb-2">Download Example Excel</a>
                                <form action="{{route('admin.users.customer.upload-excel')}}" method="post" enctype="multipart/form-data">
                                    @csrf

                                    <input type="file" name="file" class="form-control"  accept=".xls,.xlsx" />
                                    <button type="submit" class="btn btn-primary btn--primary ">Upload
                                    </button>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </h1>
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header border-0  py-2">
                <div class="search--button-wrapper justify-content-end">


                    <div class="col-sm-auto min--240">
                        <select name="zone_id" class="form-control js-select2-custom set-filter" data-filter="zone_id"
                            data-url="{{ url()->full() }}">
                            <option value="all">{{ translate('messages.All_Zones') }}</option>
                            @foreach (\App\Models\Zone::orderBy('name')->get() as $z)
                                <option value="{{ $z['id'] }}"
                                    {{ request()->get('zone_id') == $z['id'] ? 'selected' : '' }}>
                                    {{ $z['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-auto min--240">
                        <select name="order_wise" class="form-control js-select2-custom set-filter" data-filter="order_wise"
                            data-url="{{ url()->full() }}">
                            <option {{ request()->get('order_wise') == 'top' ? 'selected' : '' }} value="top">
                                {{ translate('messages.Total_orders') }} ({{ translate('messages.High_to_Low') }})
                            </option>
                            <option {{ request()->get('order_wise') == 'least' ? 'selected' : '' }} value="least">
                                {{ translate('messages.Total_orders') }} ({{ translate('messages.Low_to_High') }})
                            </option>
                            <option {{ request()->get('order_wise') == 'latest' ? 'selected' : '' }} value="latest">
                                {{ translate('messages.New_Customers') }}</option>
                        </select>
                    </div>

                    <div class="col-sm-auto min--240">
                        <select name="filter" class="form-control js-select2-custom set-filter" data-filter="filter"
                            data-url="{{ url()->full() }}">
                            <option {{ request()->get('filter') == 'all' ? 'selected' : '' }} value="all">
                                {{ translate('messages.All_Customers') }}</option>
                            <option {{ request()->get('filter') == 'active' ? 'selected' : '' }} value="active">
                                {{ translate('messages.Active_Customers') }}</option>
                            <option {{ request()->get('filter') == 'blocked' ? 'selected' : '' }} value="blocked">
                                {{ translate('messages.Inactive_Customers') }}</option>
                        </select>
                    </div>
                    <form class="search-form">
                        <!-- Search -->
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" name="search" class="form-control min-height-40"
                                value="{{ request()->get('search') }}"
                                placeholder="{{ translate('ex:_name_email_or_phone') }}" aria-label="Search">
                            <button type="submit" class="btn btn--secondary min-height-40"><i
                                    class="tio-search"></i></button>

                        </div>
                        <!-- End Search -->
                    </form>
                    @if (request()->get('search'))
                        <button type="reset" class="btn btn--primary ml-2 location-reload-to-base"
                            data-url="{{ url()->full() }}">{{ translate('messages.reset') }}</button>
                    @endif

                    <!-- Unfold -->
                    <div class="hs-unfold mr-2">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle min-height-40"
                            href="javascript:;"
                            data-hs-unfold-options='{
                                    "target": "#usersExportDropdown",
                                    "type": "css-animation"
                                }'>
                            <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                        </a>

                        <div id="usersExportDropdown"
                            class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                            <span class="dropdown-header">{{ translate('messages.options') }}</span>
                            <a id="export-copy" class="dropdown-item" href="javascript:;">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/illustrations/copy.svg"
                                    alt="Image Description">
                                {{ translate('messages.copy') }}
                            </a>
                            <a id="export-print" class="dropdown-item" href="javascript:;">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/illustrations/print.svg"
                                    alt="Image Description">
                                {{ translate('messages.print') }}
                            </a>
                            <div class="dropdown-divider"></div>
                            <span class="dropdown-header">{{ translate('messages.download_options') }}</span>
                            <a id="export-excel" class="dropdown-item"
                                href="{{ route('admin.customer.export', ['type' => 'excel', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                    alt="Image Description">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item"
                                href="{{ route('admin.customer.export', ['type' => 'csv', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                    alt="Image Description">
                                .{{ translate('messages.csv') }}
                            </a>
                        </div>
                    </div>
                    <!-- End Unfold -->

                    <!-- Unfold -->
                    <div class="hs-unfold">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-white min-height-40" href="javascript:;"
                            data-hs-unfold-options='{
                                    "target": "#showHideDropdown",
                                    "type": "css-animation"
                                }'>
                            <i class="tio-table mr-1"></i> {{ translate('messages.columns') }} <span
                                class="badge badge-soft-dark rounded-circle ml-1"></span>
                        </a>

                        <div id="showHideDropdown"
                            class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-right dropdown-card min--240">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="mr-2">{{ translate('messages.name') }}</span>

                                        <!-- Checkbox Switch -->
                                        <label class="toggle-switch toggle-switch-sm" for="toggleColumn_name">
                                            <input type="checkbox" class="toggle-switch-input" id="toggleColumn_name"
                                                checked>
                                            <span class="toggle-switch-label">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                        <!-- End Checkbox Switch -->
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="mr-2">{{ translate('messages.contact_information') }}</span>

                                        <!-- Checkbox Switch -->
                                        <label class="toggle-switch toggle-switch-sm" for="toggleColumn_email">
                                            <input type="checkbox" class="toggle-switch-input" id="toggleColumn_email"
                                                checked>
                                            <span class="toggle-switch-label">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                        <!-- End Checkbox Switch -->
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="mr-2">{{ translate('messages.total_order') }}</span>

                                        <!-- Checkbox Switch -->
                                        <label class="toggle-switch toggle-switch-sm" for="toggleColumn_total_order">
                                            <input type="checkbox" class="toggle-switch-input"
                                                id="toggleColumn_total_order" checked>
                                            <span class="toggle-switch-label">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                        <!-- End Checkbox Switch -->
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="mr-2">{{ translate('messages.active/Inactive') }}</span>

                                        <!-- Checkbox Switch -->
                                        <label class="toggle-switch toggle-switch-sm" for="toggleColumn_status">
                                            <input type="checkbox" class="toggle-switch-input" id="toggleColumn_status"
                                                checked>
                                            <span class="toggle-switch-label">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                        <!-- End Checkbox Switch -->
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="mr-2">{{ translate('messages.actions') }}</span>

                                        <!-- Checkbox Switch -->
                                        <label class="toggle-switch toggle-switch-sm" for="toggleColumn_actions">
                                            <input type="checkbox" class="toggle-switch-input" id="toggleColumn_actions"
                                                checked>
                                            <span class="toggle-switch-label">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                        <!-- End Checkbox Switch -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Unfold -->
                </div>
                <!-- End Row -->
            </div>
            <!-- End Header -->

            <div class="card-body p-0">
                <!-- Table -->
                <div class="table-responsive datatable-custom">
                    <table id="datatable"
                        class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                        data-hs-datatables-options='{
                            "columnDefs": [{
                                "targets": [0],
                                "orderable": false
                            }],
                            "order": [],
                            "info": {
                            "totalQty": "#datatableWithPaginationInfoTotalQty"
                            },
                            "search": "#datatableSearch",
                            "entries": "#datatableEntries",
                            "pageLength": 25,
                            "isResponsive": false,
                            "isShowPaging": false,
                            "paging":false
                        }'>
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">
                                    {{ translate('sl') }}
                                </th>
                                <th class="table-column-pl-0 border-0">{{ translate('messages.name') }}</th>
                                <th class="border-0">{{ translate('messages.contact_information') }}</th>
                                <th class="border-0">{{ translate('messages.total_order') }}</th>
                                <th class="border-0">{{ translate('messages.total_order_amount') }}</th>
                                <th class="border-0">{{ translate('messages.Joining_date') }}</th>
                                <th class="border-0">Status</th>
                                <th class="border-0">{{ translate('messages.actions') }}</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                            @foreach ($customers as $key => $customer)
                                <tr class="">
                                    <td class="">
                                        {{ $key + $customers->firstItem() }}
                                    </td>
                                    <td class="table-column-pl-0">
                                        <a href="{{ route('admin.users.customer.view', [$customer['id']]) }}"
                                            class="text--hover">
                                            {{ $customer['f_name'] . ' ' . $customer['l_name'] }}
                                        </a>
                                    </td>
                                    <td>
                                        <div>
                                            <a href="mailto:{{ $customer['email'] }}">
                                                {{ $customer['email'] }}
                                            </a>
                                        </div>
                                        <div>
                                            <a href="javascript:;" style="cursor:default;"
                                                class="textToCopy">{{ $customer['phone'] }}</a>
                                            <button class="copy-btn bg-transparent outline-none border-0">
                                                <i class="tio-copy"></i>
                                            </button>

                                        </div>
                                    </td>
                                    <td>
                                        <label class="badge">
                                            {{ $customer->orders_count }}
                                        </label>
                                    </td>
                                    <td>
                                        <label class="badge">
                                            {{ \App\CentralLogics\Helpers::format_currency($customer->orders()->where('order_status', '!=', 'canceled')->sum('order_amount')) }}
                                        </label>
                                    </td>
                                    <td>
                                        <label class="badge">
                                            {{ \App\CentralLogics\Helpers::date_format($customer->created_at) }}
                                        </label>
                                    </td>
                                    <td>
                                        @if($customer->status)
                                            <span class="badge badge-soft-success">Active</span>
                                        @else
                                            <span class="badge badge-soft-danger">Blocked</span>
                                        @endif
                                    </td>
                                    <td>
                                     <button class="btn p-1 dropdown-toggle" type="button"
                                                        data-toggle="dropdown" aria-expanded="false">
                                                        <img style="    width: 24px; filter: contrast(0);"
                                                            src = "{{ asset('storage/app/public/util/10025520.png') }}"
                                                            alt="action">
                                                    </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item"
                                                    href="{{ route('admin.users.customer.view', [$customer->id]) }}">
                                                    <i class="tio-visible-outlined mr-2"></i> View
                                                </a>
                                                <a class="dropdown-item"
                                                    href="javascript:;"
                                                    data-toggle="modal"
                                                    data-target="#editModal-{{ $customer->id }}">
                                                    <i class="tio-edit mr-2"></i> Edit
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                @if($customer->status)
                                                <a class="dropdown-item text-warning"
                                                    href="javascript:;"
                                                    data-toggle="modal"
                                                    data-target="#blockModal-{{ $customer->id }}">
                                                    <i class="tio-block mr-2"></i> Block
                                                </a>
                                                @else
                                                <a class="dropdown-item text-success"
                                                    href="{{ route('admin.users.customer.status', [$customer->id, 1]) }}">
                                                    <i class="tio-checkmark-circle mr-2"></i> Unblock
                                                </a>
                                                @endif
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-danger"
                                                    href="javascript:;"
                                                    data-toggle="modal"
                                                    data-target="#deleteModal-{{ $customer->id }}">
                                                    <i class="tio-delete mr-2"></i> Delete
                                                </a>
                                            </div>

                                        {{-- Block Modal --}}
                                        @if($customer->status)
                                        <div class="modal fade" id="blockModal-{{ $customer->id }}" tabindex="-1" role="dialog">
                                            <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                                                <div class="modal-content" style="border-radius:14px;border:none;text-align:center;overflow:hidden;">
                                                    <div class="modal-body p-4">
                                                        <div style="width:56px;height:56px;border-radius:50%;background:#fff8e1;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:24px;color:#f57c00;">
                                                            <i class="tio-block"></i>
                                                        </div>
                                                        <h5 class="font-semibold mb-2">Block Customer?</h5>
                                                        <p class="text-muted mb-4" style="font-size:13px;">
                                                            <strong>{{ $customer->f_name }}</strong> will be logged out immediately and cannot access the platform.
                                                        </p>
                                                        <a href="{{ route('admin.users.customer.status', [$customer->id, 0]) }}"
                                                            class="btn btn-warning text-white w-100 mb-2">Yes, Block</a>
                                                        <button type="button" class="btn btn-white w-100" data-dismiss="modal">Cancel</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        {{-- Edit Modal --}}
                                        <div class="modal fade" id="editModal-{{ $customer->id }}" tabindex="-1" role="dialog">
                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                <div class="modal-content" style="border-radius:14px;border:none;overflow:hidden;">
                                                    <form action="{{ route('admin.customer.update', $customer->id) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header" style="background:#f8fffe;border-bottom:1px solid #e0f2f1;">
                                                            <h5 class="modal-title font-semibold">
                                                                <i class="tio-edit mr-1" style="color:#00696e;"></i> Edit Customer
                                                            </h5>
                                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col-12 mb-3">
                                                                    <label class="input-label">Name <span class="text-danger">*</span></label>
                                                                    <input type="text" name="f_name" class="form-control" value="{{ $customer->f_name }}" required>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="input-label">Phone <span class="text-danger">*</span></label>
                                                                    <input type="text" name="phone" class="form-control" value="{{ $customer->phone }}" required>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="input-label">Email</label>
                                                                    <input type="email" name="email" class="form-control" value="{{ $customer->email }}">
                                                                </div>
                                                                <div class="col-12 mb-3">
                                                                    <label class="input-label">GST Number</label>
                                                                    <input type="text" name="gst" class="form-control" value="{{ $customer->gst }}">
                                                                </div>
                                                                <div class="col-12 mb-0">
                                                                    <label class="input-label">Address</label>
                                                                    <textarea name="address" class="form-control" rows="2">{{ $customer->address }}</textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer" style="border-top:1px solid #f0f0f0;">
                                                            <button type="button" class="btn btn-white" data-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn--primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Delete Modal --}}
                                        <div class="modal fade" id="deleteModal-{{ $customer->id }}" tabindex="-1" role="dialog">
                                            <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                                                <div class="modal-content" style="border-radius:14px;border:none;text-align:center;overflow:hidden;">
                                                    <div class="modal-body p-4">
                                                        <div style="width:56px;height:56px;border-radius:50%;background:#fff0f0;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:24px;color:#e74c3c;">
                                                            <i class="tio-delete"></i>
                                                        </div>
                                                        <h5 class="font-semibold mb-2">Delete Customer?</h5>
                                                        <p class="text-muted mb-4" style="font-size:13px;">
                                                            <strong>{{ $customer->f_name }}</strong> will be permanently deleted.
                                                        </p>
                                                        <form action="{{ route('admin.customer.delete', $customer->id) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger w-100 mb-2">Yes, Delete</button>
                                                            <button type="button" class="btn btn-white w-100" data-dismiss="modal">Cancel</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- End Table -->
            </div>

            @if (count($customers) !== 0)
                <hr>
            @endif
            <div class="page-area">
                {!! $customers->links() !!}
            </div>
            @if (count($customers) === 0)
                <div class="empty--data">
                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                    <h5>
                        {{ translate('no_data_found') }}
                    </h5>
                </div>
            @endif

        </div>
        <!-- End Card -->
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/customer-list.js"></script>
    <script>
        "use strict";

        $('.status_change_alert').on('click', function(event) {
            let url = $(this).data('url');
            let message = $(this).data('message');
            status_change_alert(url, message, event)
        })

        function status_change_alert(url, message, e) {
            e.preventDefault();
            Swal.fire({
                title: '{{ translate('messages.Are you sure?') }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = url;
                }
            })
        }
        $(document).ready(function() {
            $(".copy-btn").on("click", function() {
                // Get the previous <p> or span element text
                var text = $(this).prev(".textToCopy").text().trim();
                console.log(text); // Debugging

                if (navigator.clipboard && window.isSecureContext) {
                    // Modern way to copy
                    navigator.clipboard.writeText(text).then(() => {
                        console.log("Copied successfully!");
                    }).catch(err => {
                        console.error("Clipboard copy failed", err);
                    });
                } else {
                    // Fallback for older browsers
                    var tempInput = $("<textarea>"); // Use textarea instead of input
                    $("body").append(tempInput);
                    tempInput.val(text).css({
                        position: "absolute",
                        left: "-9999px", // Hide offscreen
                    }).select();
                    document.execCommand("copy");
                    tempInput.remove();
                }
                $(this).html("Copied!");
                setTimeout(() => $(this).html('<i class="tio-copy"></i>'), 1000);
            });
        });
    </script>
@endpush
