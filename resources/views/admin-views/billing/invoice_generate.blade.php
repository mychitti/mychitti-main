@extends('layouts.admin.app')

@section('title', 'Bills')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (Config::get('module.current_module_id') == 6)
        <style>
            .hidden_hsn {
                display: none;
            }
        </style>
    @endif
    <style>
        .form-row {
            margin-top: 6px;
        }


        .hidden_tax {
            display: none;
        }

        @media (max-width: 768px) {
            table {
                display: block;
                /* Make table block */
                border: none;
            }

            thead {
                display: none;
                /* Hide headers */
            }

            tbody tr {
                display: block;
                margin-bottom: 10px;
                border: 1px solid #ddd;
                /* Add border around cards */
                padding: 10px;
            }

            tbody td {
                display: flex;
                justify-content: space-between;
                padding: 5px 10px;
            }

            tbody td::before {
                content: attr(data-label);
                /* Use data-label for headings */
                font-weight: bold;
                flex: 1;
            }

            td {
                flex: 2;
            }
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Bills</h1>
            <div class="page-header-select-wrapper">
                <!-- Button trigger modal -->
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
                    Correct an Invoice
                </button>

                <!-- Modal -->
                <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Invoice Correction</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('admin.billing.invoice-correction') }}" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Invoice Id</label>
                                        <input type="text" class="form-control" name="invoice_id" id="exampleInputEmail1"
                                            aria-describedby="emailHelp">
                                    </div>

                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Page Header -->



        {{-- <div class="row ">
            <form class="w-100" action="{{ route('admin.billing.save-manual-invoice') }}" method="post">
                @csrf
                <input type="hidden" id="service_id" name="service_id" value="">
                <div class="my-3">
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" value="user" checked id="customRadioInline1" name="bill_to_type"
                            class="custom-control-input bill_to_type">
                        <label class="custom-control-label " for="customRadioInline1">Customer</label>
                    </div>
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" value="vendor" id="customRadioInline2" name="bill_to_type"
                            class="custom-control-input bill_to_type">
                        <label class="custom-control-label " for="customRadioInline2">Store</label>
                    </div>
                </div>
                <div class="row">
                <div class="form-check  col-md-4 col-sm-6">
                    <div id="customer_list">
                        <label class="form-check-label" for="flexRadioDefault2">Customer</label>
                        <select name="bill_to" class="form-control js-select2-custom customer_id check-addr"
                            data-type="customer">
                            <option value=""></option>
                            <option value="add_new">Add New</option>

                            @foreach ($customers as $cust)
                                <option value="{{ $cust->id }}">
                                    {{ $cust->phone . ' | ' . $cust->f_name . ' ' . $cust->l_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display:none;" id="store_list">
                        <label class="form-check-label" for="flexRadioDefault2">Store</label>
                        <select name="" class="form-control js-select2-custom store_id check-addr" data-type="store">
                            <option value=""></option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}">
                                    {{ $store->phone . ' | ' . $store->name }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
               <div class="form-check  col-md-3 ">
                    <label class="form-check-label d-flex " for="flexRadioDefault2">Invoice Date</label>
                    <div id="">
                        @php
                            $today = date('Y-m-d');
                            $startOfFinancialYear = date('Y') -1 . '-04-01';
                        @endphp

                        <input type="date" name="invoice_date" class="form-control" min="{{ $startOfFinancialYear }}"
                            value="{{ $today }}">
                    </div>
                </div>
                </div>

                <div class="">
                    <div class="card h-100">
                        <div class="card-body row item_row">
                            <button type="button" class="btn btn-dark btn-sm" onclick="addMoreRow()">Add More</button>
                            <table class="table">
                                <thead class="" style=" background: #75b8b8; color: white;">
                                    <tr>
                                        <th scope="col">Item</th>
                                        <th scope="col">Price</th>
                                        <th scope="col">Qty</th>
                                        <th class="tax_inp_data " scope="col">Tax <i>(in %)</i></th>
                                        <th class="hsn_inp " scope="col">HSN</th>
                                        <th scope="col"></th>
                                    </tr>
                                </thead>
                                <tbody class="rows_parent">
                                    <tr class="item_row" data-id="1">
                                        <input type="hidden" name="invoice_item_id[]">
                                        <td><input type="text" name="item_name[]" placeholder="Item Name"
                                                class="form-control"></td>
                                        <td><input type="number" step="0.001" name="item_price[]" placeholder="Price"
                                                class="form-control"></td>
                                        <td><input type="number" name="item_qty[]" placeholder="Quantity"
                                                class="form-control"></td>
                                        <td class="tax_inp_data "><input type="number" name="item_tax[]"
                                                placeholder="Tax" class="form-control "></td>
                                        <td class="hsn_inp "><input type="text" name="item_hsn[]" placeholder="HSN"
                                                class="form-control">
                                        </td>
                                        <td><button type="button" onclick="deleteNewRow('invoice')"
                                                class="btn action-btn btn--danger btn-outline-danger"><i
                                                    class="tio-delete-outlined"></i></button></td>
                                    </tr>


                                </tbody>
                            </table>

                            <div class="row w-100 mb-2">
                                <div class="form-check mr-5 ml-4">
                                    <input class="form-check-input tax_type" value="gst" name="tax_type"
                                        type="radio" id="gstRadio1" checked>
                                    <label class="form-check-label" for="gstRadio1">
                                        GST
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input tax_type" value="non-gst" name="tax_type"
                                        type="radio" id="gstRadio2">
                                    <label class="form-check-label" for="gstRadio2">
                                        Non GST
                                    </label>
                                </div>
                            </div>
                            <div class="row w-100 mb-2">
                                <div class="form-check mr-5 ml-4">
                                    <input class="form-check-input" value="Paid" name="payment_stts" type="radio"
                                        id="flexRadioDefault1" checked>
                                    <label class="form-check-label" for="flexRadioDefault1">
                                        Paid
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" value="Unpaid" name="payment_stts" type="radio"
                                        name="flexRadioDefault" id="flexRadioDefault2">
                                    <label class="form-check-label" for="flexRadioDefault2">
                                        Unpaid
                                    </label>
                                </div>
                            </div>

                            <div class="form-check payment_date_inp col-md-4 col-sm-6" style="display:none;">
                                <label class="form-check-label" for="flexRadioDefault2">Payment Date</label>
                                <input class="form-control" min="{{ date('Y-m-d') }}" name="payment_date" type="date"
                                    name="flexRadioDefault" id="flexRadioDefault2">
                            </div>
                            <div class="form-check reminder_date_inp col-md-4 col-sm-6" style="display:none;">
                                <label class="form-check-label" for="flexRadioDefault2">Reminder Date</label>
                                <input class="form-control" min="{{ date('Y-m-d') }}" name="reminder_date"
                                    type="date" name="flexRadioDefault" id="flexRadioDefault2">
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary my-2">Generate Bill</button>
                </div>
            </form>
        </div> --}}
   
        <!-- Page Header -->
      
        <!-- End Page Header -->


        <div class="card mt-3">
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{ translate('messages.invoice_list') }}<span
                            class="badge badge-soft-dark ml-2" id="itemCount">{{ count($invoices) }}</span></h5>

                </div>
                <a href="" class="text-danger text-underline mx-2" data-toggle="modal"
                    data-target="#deleteInvModal">Delete By Serial Numebr</a>
                <form action="" class="row search-form">
                    <div class="col">
                        <input type="date" name="from" value="{{ $fromdate }}" class="form-control"
                            id="">
                    </div>
                    <div class="col">
                        <input type="date" name="to" value="{{ $todate }}" class="form-control"
                            id="">
                    </div>
                    <div class="col">
                        <button class="btn btn-primary btn-sm">Filter</button>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable"
                        class="table table-borderless table-thead-bordered table-align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('sl') }}</th>
                                <th class="border-0">Invoice Id</th>
                                <th class="border-0 ">Bill to</th>
                                <th class="border-0 ">Type</th>
                                <th class="border-0 ">Total Amount</th>
                                <th class="border-0 ">Payment Method</th>
                                <th class="border-0 ">Payment Status</th>
                                <th class="border-0 ">Payment Date</th>
                                <th class="border-0 ">Created At</th>
                                <th class="border-0 ">Action</th>
                            </tr>
                        </thead>

                        <tbody id="table-div">
                            @foreach ($invoices as $key => $invoice)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $invoice->invoice_id }}</td>
                                    <td>
                                        @if ($invoice->bill_to_type == 'vendor')
                                            {{ _getUserDetails($invoice->bill_to, 'store') ? _getUserDetails($invoice->bill_to, 'store')->name : 'Store Deleted' }}
                                            <br> Store ID : {{ $invoice->bill_to }}
                                        @else
                                            {{ _getUserDetails($invoice->bill_to) ? _getUserDetails($invoice->bill_to)->f_name . ' ' . _getUserDetails($invoice->bill_to)->l_name : 'Customer Deleted' }}
                                            <br> Customer ID : {{ $invoice->bill_to }}
                                        @endif
                                    </td>
                                    <td>{{ $invoice->type }}</td>
                                    <td>{{ \App\CentralLogics\Helpers::currency_symbol() . number_format($invoice->total_amount) }}
                                    </td>
                                    <td>{{ $invoice->payment_method }}</td>
                                    <td>{{ $invoice->payment_status }}</td>
                                    <td>{{ $invoice->payment_date ? $invoice->payment_date : explode(' ', $invoice->created_at)[0] }}
                                    </td>
                                    <td>{{ $invoice->created_at }}</td>
                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <!-- $invoice->invoice_id -->
                                            @if ($invoice->pdf)
                                                <a class="btn action-btn btn--primary btn-outline-primary" target="_blank"
                                                    href="{{ asset('storage/app/public/invoice') . '/' . $invoice->pdf }}"
                                                    title="View">
                                                    <i class="tio-visible"></i>
                                                </a>
                                            @else
                                                @if ($invoice->bill_to_type == 'vendor')
                                                    @if (_checkUser($invoice->bill_to, 'vendor'))
                                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                                            target="_blank"
                                                            href="{{ route('admin.billing.invoice-view', [$invoice->invoice_id]) }}?store"
                                                            title="View">
                                                            <i class="tio-visible"></i>
                                                        </a>
                                                    @else
                                                        <a style="width: fit-content;"
                                                            class="btn action-btn btn--danger btn-outline-danger">
                                                            Store deleted
                                                        </a>
                                                    @endif
                                                @else
                                                    @if (_checkUser($invoice->bill_to, 'user'))
                                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                                            target="_blank"
                                                            href="{{ route('admin.billing.invoice-view', [$invoice->invoice_id]) }}"
                                                            title="View">
                                                            <i class="tio-visible"></i>
                                                        </a>
                                                    @else
                                                        <a style="width: fit-content;"
                                                            class="btn action-btn btn--danger btn-outline-danger">
                                                            User deleted
                                                        </a>
                                                    @endif
                                                @endif
                                            @endif
                                            <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                href="javascript:" data-id="category-{{ $invoice['invoice_id'] }}"
                                                data-message="{{ translate('Want to delete this invoice') }}"
                                                title="{{ translate('messages.delete_invoice') }}"><i
                                                    class="tio-delete-outlined"></i>
                                            </a>
                                            <form
                                                action="{{ route('admin.billing.invoice-delete', [$invoice->type, $invoice['invoice_id']]) }}"
                                                method="get" id="category-{{ $invoice['invoice_id'] }}">
                                                @csrf @method('get')
                                            </form>
                                        </div>
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if (count($invoices) !== 0)
                <hr>
            @endif
            @if (count($invoices) === 0)
                <div class="empty--data">
                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                    <h5>
                        {{ translate('no_data_found') }}
                    </h5>
                </div>
            @endif
        </div>

    </div>
    <div class="modal fade" id="deleteInvModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Delete By Serial Numbers</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('admin.billing.invoice-bulk-delete')}}" method="post">
                    @csrf
                    <div class="modal-body">
                        <label for="">Serial No. <i class="tio-info-outined"
                                title="All invoices between (and including) entered serial numbers will be deleted"></i></label>
                        <div class="d-flex">
                            <input type="number" placeholder="Ex: 1" name="from" style="width:100px" class="form-control">
                            <span class="p-2">To</span>
                            <input type="number" placeholder="Ex: 3" name="to" style="width:100px" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="pincodeModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form class="pincode_form" action="{{ route('admin.users.customer.save-pincode') }}" method="post">
                        <div class="form-group">
                            <label for="exampleInputEmail1">Bill to Pincode</label>
                            <input name="user_id" type="hidden" class="user_id_inp">
                            <input name="type" type="hidden" class="type_inp">
                            <input name="pin_code" type="number" class="form-control pincode_inp"
                                id="exampleInputEmail1" aria-describedby="emailHelp">
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('script_2')
    <script>
        $(document).on('change', 'input[name="payment_stts"]', function() {
            var val = $(this).val();
            if (val == 'Paid') {
                $(".payment_date_inp").hide()
                $(".reminder_date_inp").hide()
            } else {
                $(".payment_date_inp").show()
                $(".reminder_date_inp").show()
            }
        })

        function toasterNotification(msg) {
            $("#toast").text(msg);
            $("#toast").addClass("show");
            setTimeout(function() {
                $("#toast").removeClass("show");
            }, 3000);
        }
        $(".tax_type").on('change', function() {
            if ($(this).val() == 'non-gst') {
                $('.tax_inp_data').addClass('hidden_tax')
                $('.hsn_inp').addClass('hidden_hsn')
            } else {
                $('.tax_inp_data').removeClass('hidden_tax')
                $('.hsn_inp').removeClass('hidden_hsn')
            }
        })

        function deleteNewRow(rowId) {
            $('[data-id="' + rowId + '"]').remove()
        }

        $("#customer_id").on('change', function() {
            if ($(this).val() == 'add_new') {
                $('#addCustomerModal').modal('show')
            }
        })

        function addMoreRow() {

            var $lastItemRow = $('.item_row').last();

            if (!$lastItemRow.length) {
                var dataId = 1;
            } else {
                var dataId = Number($lastItemRow.data('id')) + 1;

            }
            console.log(dataId)
            var className = '';
            if ($(".tax_type:checked").val() == 'non-gst') {
                className = 'hidden_tax';
                className2 = 'hidden_hsn';
            } else {
                className = '';
                className2 = '';
            }

            var html = `<tr class="item_row" data-id="` + dataId + `">
                       <input type="hidden" name="invoice_item_new[]" value="1" placeholder="Item Name" class="form-control">
                      <td><input type="text" name="item_name_new[]" placeholder="Item Name" class="form-control"></td>
                      <td><input type="number" step="0.001" name="item_price_new[]" placeholder="Price" class="form-control"></td>
                      <td><input type="number" name="item_qty_new[]" placeholder="Qunatity" class="form-control"></td>
                      <td class="tax_inp_data ` + className + `"><input type="number" name="item_tax_new[]" placeholder="Tax" class="form-control"></td>
                      <td class="hsn_inp ` + className2 + `"><input type="text" name="item_hsn_new[]" placeholder="HSN" class="form-control"></td>
                       <td><button type="button"  onclick="deleteNewRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                    </tr>`;

            $('.rows_parent').append(html)
        }
        $(".bill_to_type").on('change', function() { 
            if ($(this).val() == 'user' && $(this).prop('checked') == true) {
                console.log('user');
                $('#store_list').hide();
                $('.store_id').attr('name', '')

                $('#customer_list').show();
                $('.customer_id').attr('name', 'bill_to')

            } else {
                console.log('vendor');
                $('#store_list').show();
                $('.store_id').attr('name', 'bill_to')

                $('#customer_list').hide();
                $('.customer_id').attr('name', '')

            }
        });

        $(".check-addr").on('change', function() {
            let id = $(this).val();
            let type = $(this).data('type'); // either 'customer' or 'store'

            let url = '';
            let inputSelector = '';

            url = "{{ route('admin.users.customer.check-addr') }}";
            inputSelector = '.user_id_inp'

            if (!url || !id) return;

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.post({
                url: url,
                data: {
                    user_id: id,
                    type: type
                },
                success: function(data) {
                    if (data == 1) {
                        // Address exists
                    } else {
                        // Show pincode modal
                        $('#pincodeModal').modal('show');
                        $(inputSelector).val(id);
                        $('.type_inp').val(type);
                    }
                },
            });
        });
        $(".pincode_form").on('submit', function(e) {
            e.preventDefault();
            var formdata = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: formdata,
                processData: false, // important for FormData
                contentType: false, // important for FormData
                success: function(data) {
                    console.log(data);
                    if (data.status) {
                        toastr.success(data.msg);
                        $('#pincodeModal').modal('hide')
                        $('.user_id_inp').val('')
                        $('.pincode_inp').val('')
                    } else {
                        toastr.error(data.msg);
                    }
                }
            });
        });
    </script>
@endpush
