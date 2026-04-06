@extends('layouts.admin.app')

@section('title', 'Invoices')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        @media (max-width:550px) {
            .mini-date {
                width: 30px;
                padding: 5px;
            }
        }
    </style>
    <style>
        .dropdown-toggle:not(.dropdown-toggle-empty)::after {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid p-2">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Invoices<span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($invoices) }}</span></h1>
            <div class="page-header-select-wrapper">


            </div>
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2 px-1">

                <div class="search--button-wrapper">
                    {{-- <h5 class="card-title">{{ $type . ' Leads' }}</h5> --}}
                    <form action="" class="d-flex date-range-form">
                        <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning" type="button"
                            data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>

                        {{-- date range modal --}}
                        @include('admin-views/form_modals/date_range')

                        <select class="form-control mx-1" name="status" onchange="this.form.submit()">
                            <option {{ $status == 'all' ? 'selected' : '' }} value="all">All</option>
                            <option {{ $status == 'credit' ? 'selected' : '' }} value="credit">Credit</option>
                            <option {{ $status == 'pending' ? 'selected' : '' }} value="pending">Pending</option>
                            <option {{ $status == 'overdue' ? 'selected' : '' }} value="overdue">Overdue</option>

                        </select>
                        {{-- <button class="btn btn-primary"><i class="tio-filter-outlined"></i></button> --}}
                    </form>
                    <small class="px-2 d-sm-block d-md-none d-lg-none">
                        From {{ $from }} to {{ $to }}
                    </small>

                    {{-- <a href="{{ route('admin.billing.export') }}" class="btn btn--primary">Export All
                    </a> --}}
                    @if (hasPermission('billing', 'export'))
                        <form id="" action= "{{ route('admin.billing.export') }}" method="POST" target="_blank">
                            @csrf
                            <input type="hidden" name="type" value = "all">
                            <button type="submit" class="btn btn--primary  btn_sm">Export All
                            </button>
                        </form>
                    @endif
                    @if (hasPermission('billing', 'import'))
                        <button data-toggle="modal" data-target="#importExcelModal" class="btn btn_sm btn--primary">Import
                            Excel</button>
                    @endif
                    @if (hasPermission('billing', 'export'))
                        <form id="exportForm" action="{{ route('admin.billing.export') }}" method="POST" target="_blank">
                            @csrf
                            <input type="hidden" name="type" value = "selected">
                            <input type="hidden" name="selected_items" id="selected_items_input">
                            <button type="button" class="btn btn_sm btn--primary export_selected">Export Selected
                            </button>
                        </form>
                    @endif
                    <form class="search-form">
                        <!-- Search -->
                        <div class="input-group input--group">
                            <input type="search" name="search" value="{{ request()?->search ?? null }}"
                                class="form-control min-height-45"
                                placeholder="{{ translate('messages.search by invoice id') }}">
                            <button type="submit" class="btn btn--secondary min-height-45"><i
                                    class="tio-search"></i></button>
                        </div>
                        <!-- End Search -->
                    </form>
                    @if (request()->get('search'))
                        <a href="{{ route('admin.billing.list') }}"
                            class="btn btn--primary ml-2 location-reload-to-category">{{ translate('messages.reset') }}</a>
                    @endif

                </div>
            </div>
            <!-- End Header -->
            @if (hasPermission('billing', 'list'))

                <!-- Table -->
                <div class="table-responsive datatable-custom" style="    min-height: 200px;">
                    <table id="columnSearchDatatable"
                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                        data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false

                        }'>
                        <thead class="thead-light">
                            <tr>
                                @if (hasPermission('billing', 'export'))
                                    <th class="border-0"><input type="checkbox" class="check_all" name=""
                                            id="">
                                    </th>
                                @endif
                                <th class="border-0">{{ translate('sl') }}</th>
                                <th class="border-0">Invoice Id</th>
                                <th class="border-0">Client Name</th>
                                <th class="border-0">Payment Status</th>
                                <th class="border-0">Invoice Status</th>
                                <th class="border-0">Payment Date</th>
                                <th class="border-0">Amount</th>
                                <th class="border-0">Date</th>
                                <th class="border-0">Action</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                            @foreach ($invoices as $key => $conf)
                                <tr>
                                    @if (hasPermission('billing', 'export'))
                                        <td><input type="checkbox" value="{{ $conf->id }}" class="check_item"
                                                name="" id=""></td>
                                    @endif
                                    <td>{{ $key + 1 }}</td>
                                    <td> {{ $conf->invoice_id }} </td>
                                    <td> {{ $conf->user?->f_name . ' ' . $conf->user?->l_name }} </td>
                                    <td>
                                        @if (strtolower($conf->payment_status) == 'paid')
                                            <span class="badge badge-soft-success">
                                                {{ translate('messages.paid') }}
                                            </span>
                                        @else
                                            @if (hasPermission('billing', 'pay'))
                                                <a style="width: 74px;"
                                                    class="btn action-btn btn--warning btn-outline-warning mark_paid_btn"
                                                    data-toggle="modal" data-id ="{{ $conf->id }}"
                                                    data-target="#markPaidModal" href="javascript:;">Unpaid</a>
                                            @else
                                                <span class="text-{{strtolower($conf->payment_status) == 'paid' ? 'success' : 'danger'}}">{{ucfirst($conf->payment_status)}}</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if ($conf->status == 'credit')
                                            <button
                                                style="pointer-events:none;width: 74px;background-color: #ffff81 !important;"
                                                class="btn action-btn ">Credit</button>
                                        @elseif($conf->status == 'pending')
                                            <button style="pointer-events:none;width: 74px;background-color: #ffa86b;"
                                                class="btn action-btn text-dark">Pending</button>
                                        @else
                                            <button
                                                style="pointer-events:none;width: 74px; background-color: #ff5e5e !important;"
                                                class="btn action-btn text-light">Overdue</button>
                                        @endif
                                    </td>
                                    <td> {{ $conf->payment_date }}</td>
                                    <td>{{ _price($conf->total_amount) }}</td>
                                    <td> {{ _formatted_datetime($conf->created_at) }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn p-1 dropdown-toggle" type="button" data-toggle="dropdown"
                                                aria-expanded="false">
                                                <img style="    width: 24px; filter: contrast(0);"
                                                    src = "{{ asset('storage/app/public/util/10025520.png') }}"
                                                    alt="action">
                                            </button>
                                            <div class="dropdown-menu">
                                                @if (hasPermission('billing', 'reminder_update') && $conf->payment_status == 'Unpaid')
                                                    <a href="{{ route('admin.billing.reminder', [$conf->type, $conf->reminder_status, $conf->id]) }}"
                                                        class="dropdown-item  text-warning"
                                                        title="{{ translate('messages.view') }}">{{ $conf->reminder_status ? 'Stop ' : 'Start ' }}Reminder
                                                    </a>
                                                @endif
                                                @if (hasPermission('billing', 'view'))
                                                    @if ($conf->pdf)
                                                        <a href="{{ asset('storage/app/public/invoice') . '/' . $conf->pdf }}"
                                                            class="dropdown-item  text-primary"
                                                            title="{{ translate('messages.view') }}"><i
                                                                class="tio-visible"></i>
                                                            View Invoice
                                                        </a>
                                                    @else
                                                        <a href="{{ route('admin.billing.invoice-view', [$conf->service_id ? 'service' : 'manual', $conf->service_id ? $conf->service_id : $conf->invoice_id]) }}"
                                                            class="dropdown-item  text-primary"
                                                            title="{{ translate('messages.view') }}"><i
                                                                class="tio-visible"></i>
                                                            View Invoice
                                                        </a>
                                                    @endif
                                                @endif
                                                @if (hasPermission('billing', 'edit'))
                                                    @if ($conf->type == 'service')
                                                        <a href="{{ route('admin.billing.edit-service-invoice', [$conf->id]) }}"
                                                            class="dropdown-item  text--primary" title="Edit"><i
                                                                class="tio-edit"></i>
                                                            Edit</a>
                                                        </a>
                                                    @else
                                                        <a href="{{ route('admin.billing.edit', [$conf->id]) }}"
                                                            class="dropdown-item  text--primary" title="Edit"><i
                                                                class="tio-edit"></i>
                                                            Edit</a>
                                                        </a>
                                                    @endif
                                                @endif
                                                @if ($conf->pdf)
                                                    @if (hasPermission('billing', 'share'))
                                                        <a href="javascript:void(0);"
                                                            onclick="shareFunction('{{ asset('storage/app/public/invoice') . '/' . $conf->pdf }}')"
                                                            class="dropdown-item  text--success" title="Share"><i
                                                                class="tio-share-vs"></i>
                                                            Share</a>
                                                        </a>
                                                    @endif
                                                    @if (hasPermission('billing', 'download'))
                                                        <a href="{{ asset('storage/app/public/invoice') . '/' . $conf->pdf }}"
                                                            download class="dropdown-item  text--warning"
                                                            title="Edit"><i class="tio-download-to"></i>
                                                            Download</a>
                                                        </a>
                                                    @endif
                                                @else
                                                    @if (hasPermission('billing', 'share'))
                                                        <a href="javascript:void(0);"
                                                            onclick="shareFunction('{{ route('admin.billing.invoice-view', [$conf->service_id ? 'service' : 'manual', $conf->service_id ? $conf->service_id : $conf->invoice_id]) }}')"
                                                            class="dropdown-item  text--success" title="Edit"><i
                                                                class="tio-share-vs"></i>
                                                            Share</a>
                                                    @endif
                                                @endif
                                                @if (hasPermission('billing', 'delete'))
                                                    <a class="dropdown-item form-alert text-danger" href="javascript:;"
                                                        data-id="customer-{{ $conf['id'] }}"
                                                        data-message="{{ translate('messages.Want to delete this invoice') }}"
                                                        title="{{ translate('messages.delete_invoice') }}"><i
                                                            class="tio-delete-outlined"></i>
                                                        Delete</a>
                                                @endif
                                            </div>
                                            @if (hasPermission('billing', 'delete'))
                                                <form
                                                    action="{{ route('admin.billing.delete', [$conf->service_id ? 'service' : 'manual', $conf->id]) }}"
                                                    method="post" id="customer-{{ $conf['id'] }}">
                                                    @csrf @method('get')
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <hr>
                    <div class="page-area">
                    </div>
                    @if (!count($invoices))
                        <div class="empty--data">
                            <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                            <h5>
                                {{ translate('no_data_found') }}
                            </h5>
                        </div>
                    @endif

                </div>
                <!-- End Table -->
            @endif
        </div>
        <!-- End Card -->
    </div>
    <div class="modal fade" id="markPaidModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Mark as Paid</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="{{ route('admin.billing.mark-paid2') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" class="invoice_id_inp">
                        <input type="hidden" name="type" value="manual" class="">
                        <div class="">
                            <div class="pos--payment-options">
                                <label class="">{{ translate('payment mode') }}</label>
                                <ul class="mb-0">
                                    <li>
                                        <label>
                                            <input type="radio" name="payment_mode" value="Cash" hidden checked>
                                            <span>Cash</span>
                                        </label>
                                    </li>
                                    <li>
                                        <label>
                                            <input type="radio" name="payment_mode" value="Online" hidden>
                                            <span>Online</span>
                                        </label>
                                    </li>
                                    <li>
                                        <label>
                                            <input type="radio" name="payment_mode" value="Cash and Online" hidden>
                                            <span>Both</span>
                                        </label>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="w-100 row">
                            <div class="col-md-6 p-1 partial_payment" style="display: none">
                                <label class="form-check-label mb-1" for="flexRadioDefault2">Cash Amount</label>
                                <input class="form-control form-control-sm cash_amount" name="cash_amount" type="number"
                                    placeholder="Ex: 2000" name="flexRadioDefault" id="flexRadioDefault2">
                            </div>
                            <div class="col-md-6 p-1 partial_payment" style="display: none">
                                <label class="form-check-label mb-1" for="flexRadioDefault2">Online Amount</label>
                                <input class="form-control form-control-sm online_amount" name="online_amount"
                                    type="number" placeholder="Ex: 3000" name="flexRadioDefault"
                                    id="flexRadioDefault2">
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Mark Paid</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Import Excel</h5>
                    <button type="button" class="close close_modal" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="importForm" action="{{ route('admin.billing.import-sheet') }}" method="post"
                        enctype="multipart/form-data">
                        @csrf
                        <a href="{{ asset('storage/app/public/util/example-bulk-bills.xlsx') }}" download
                            class="btn btn-outline-primary mb-2">View Example</a>
                        <div class="form-group">
                            <label for="file">Upload Excel File</label>
                            <input type="file" name="file" class="form-control" id="file"
                                accept=".xlsx,.xls">
                        </div>
                        <div class="form-group w-100 ">
                            <button type="submit" class="btn btn-primary float-right">Import</button>
                        </div>
                    </form>
                </div>
                <div class="p-3">
                    <h4>How It Works</h4>
                    <ol>
                        <li>Download Example Sheet. Fill data in it.</li>
                        <li><span class="text-danger">**Do not edit or delete headings**</span></li>
                        <li><strong>Temp Group</strong><br>
                            Use a short code like <code>TMP1</code>, <code>TMP2</code> to group items in the
                            same invoice.<br>
                            All items in one invoice must have the same Temp Group.
                        </li>
                        <li><strong>Invoice Details (Date, Payment, Tax, etc.)</strong><br>
                            You only need to fill these in the <strong>first row</strong> of each Temp Group
                            (e.g., the first row for TMP1, TMP2, etc.).<br>
                            Leave them blank in the other rows for that same group. (but Temp Group ID is
                            must be filled)
                        </li>

                        <li><strong>Item Details</strong><br>
                            You must fill <strong>Item Name, Price, Tax Percent, HSN, and Qty</strong> in
                            <strong>every row</strong> for each item in the invoice.
                        </li>
                        <li><strong>Invoice Date</strong><br>
                            Enter the date of the invoice (e.g., <code>05-03-2025</code>).
                        </li>
                        <li><strong>Client Phone / Client ID</strong><br>
                            Enter either client phone number or client id (e.g., <code>9988779988 /
                                23</code>).
                        </li>
                        <li><strong>Invoice Number</strong><br>
                            Enter the invoice number which is not used already (e.g., <code>PJS_260,
                                PJS_M_25-26_21</code>).
                            Leave blank for auto generate.
                        </li>

                        <li><strong>Payment Status</strong><br>
                            Type <code>Paid</code> or <code>Unpaid</code>.
                        </li>
                        <li><strong>Payment Date</strong><br>
                            If Paid, enter the payment date.<br>
                            If Unpaid, leave it blank.
                        </li>
                        <li><strong>Tax Type</strong><br>
                            Write <code>gst</code> or <code>non-gst</code>.
                        </li>
                        <li><strong>Reminder Date</strong> (if needed)<br>
                            Enter the date to send a service reminder.<br>
                            Leave blank if not needed.
                        </li>
                        <li><strong>Reminder Frequency</strong> (if needed)<br>
                            Example: <code>1</code>, <code>3</code>, etc.<br>
                            Leave blank if not needed.
                        </li>
                        <li><strong>Reminder Frequency Unit</strong> (if needed)<br>
                            Example: <code>hour</code>, <code>day</code>, <code>week</code>,
                            <code>month</code>, etc.<br>
                            Leave blank if not needed.
                        </li>
                        <li><strong>Item Name</strong><br>
                            Write the name of the product or service.
                        </li>
                        <li><strong>Item Price</strong><br>
                            Enter the price of one unit.
                        </li>
                        <li><strong>Tax Percent</strong><br>
                            Enter GST rate like <code>0</code>, <code>12</code>, or <code>18</code>.
                        </li>
                        <li><strong>HSN Code</strong><br>
                            Enter the HSN code (if applicable).
                        </li>
                        <li><strong>Qty</strong><br>
                            Enter how many units of the item.
                        </li>
                    </ol>

                    <p><strong>Tips:</strong></p>
                    <ul>
                        <li>Add a new row for each item in the invoice.</li>
                        <li>Use the same Temp Group for all items in one invoice.</li>
                        <li>Leave reminder fields blank if not needed.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        function shareFunction(url) {
            if (navigator.share) {
                navigator.share({
                        title: document.title,
                        text: 'Check this out!',
                        url: url
                    })
                    .then(() => console.log('Shared successfully'))
                    .catch((err) => console.error('Share failed:', err));
            } else {
                // Fallback: Copy to clipboard
                navigator.clipboard.writeText(url).then(() => {
                    toastr.success('Link copied to clipboard!');

                });
            }
        }

        $(document).on('click', '.pos--payment-options label', function() {
            var val = $(this).find('input[type="radio"]').val();
            var $modal = $(this).closest('.modal-content');
            if (val == 'Cash and Online') {
                $modal.find('.partial_payment').show();
            } else {
                $modal.find('.partial_payment').hide();
            }
        })
        $('#importForm').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: $(this).attr('action'),
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(data) {
                    if (data.status == 'existing') {
                        Swal.fire({
                            title: 'Duplicate Ids',
                            text: data.message,
                            type: 'warning',
                            showCancelButton: false,
                            confirmButtonColor: '#FC6A57',
                            confirmButtonText: 'Okay',
                            reverseButtons: true
                        }).then((result) => {

                        })
                    } else {
                        toastr.success(data.message);

                    }
                    $(".close_modal").click()
                },
            });
        })

        function status_change_alert(url, message, e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: 'No',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = url;
                }
            })
        }
        $(document).on('ready', function() {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            var datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

            $('#column1_search').on('keyup', function() {
                datatable
                    .columns(1)
                    .search(this.value)
                    .draw();
            });

            $('#column2_search').on('keyup', function() {
                datatable
                    .columns(2)
                    .search(this.value)
                    .draw();
            });

            $('#column3_search').on('keyup', function() {
                datatable
                    .columns(3)
                    .search(this.value)
                    .draw();
            });

            $('#column4_search').on('keyup', function() {
                datatable
                    .columns(4)
                    .search(this.value)
                    .draw();
            });


            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function() {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>

    <script>
        $('#search-form').on('submit', function() {
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('admin.store.search') }}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#set-rows').html(data.view);
                    $('#itemCount').html(data.total);
                    $('.page-area').hide();
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        });
        $(".export_selected").on('click', function() {
            let selected = [];

            $(".check_item:checked").each(function() {
                selected.push($(this).val());
            });

            if (selected.length === 0) {
                toastr.error("Please select at least one invoice.");
                return;
            }

            $("#selected_items_input").val(JSON.stringify(selected));

            $("#exportForm").submit();
        });

        $('.check_all').on('change', function() {
            if ($(this).prop('checked') == true) {
                $(".check_item").prop('checked', true)
            } else {
                $(".check_item").prop('checked', false)
            }
        })
        $('.mark_paid_btn').on('click', function() {
            let id = $(this).attr('data-id')
            $(".invoice_id_inp").val(id);
        })
    </script>
    @include('vendor-views/js/date_range')
@endpush
