@extends('layouts.vendor.app')

@section('title', 'Service Quotations')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .add_more_btn {
            display: block;
        }

        .add_more_btn_mobile {
            display: none;
        }

        /* General Styles for Table */
        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table thead th {
            text-align: left;
            padding: 8px;
        }

        .table tbody td {
            padding: 8px;
        }

        /* Ensure Input Fields Take Full Width */
        .table tbody td input {
            width: 100%;
            box-sizing: border-box;
        }

        /* Responsive Design */
        .quote-total-bar {
            background: #f8fafc;
            border: 1px solid #e3e8ef;
            border-radius: 8px;
            padding: 12px 18px;
            margin: 8px 0 12px;
            max-width: 340px;
            margin-left: auto;
        }
        .qtb-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.88rem;
            color: #677788;
            padding: 3px 0;
        }
        .qtb-grand {
            font-size: 1rem;
            font-weight: 700;
            color: #1e2022;
            border-top: 1px solid #d9e1ea;
            margin-top: 6px;
            padding-top: 6px;
        }

        @media (max-width: 768px) {
            .add_more_btn {
                display: none;
            }

            .add_more_btn_mobile {
                display: block;
            }
            .quote-total-bar { max-width: 100%; }

            /* Make Table Scrollable */
            .table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
                /* Prevent wrapping of table rows */
            }

            /* Adjust Input Field Widths */
            .table tbody td input {
                font-size: 14px;
                padding: 6px;
            }

            /* Adjust Button Size */
            .btn {
                font-size: 12px;
                padding: 6px;
            }
        }

        @media (max-width: 850px) {

            /* Stack Table Rows (Optional Alternative) */
            .table {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }

            .table thead {
                display: none;
                /* Hide headers on smaller screens */
            }

            .table tbody tr {
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
                gap: 0.5rem;
                border: 1px solid #ddd;
                padding: 8px;
            }

            .table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
            }

            .table tbody td::before {
                content: attr(data-label);
                /* Dynamically add labels for accessibility */
                flex: 1;
                font-weight: bold;
                text-transform: capitalize;
            }

            .table tbody td input {
                /* flex: 2; */
            }
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">

        <!-- Resturent Card Wrapper -->
        <div class="row">
            @php
                $gp = $allQuotations;
                $custDet = _customerDetByserviceId($serviceDetails->service_id);
            @endphp

            {{-- Left: quotation form --}}
            <div class="col-md-8">
                <h4 class="resturant-card" style="background-color:#f0f0f0">{{ $serviceDetails->item_name }}</h4>
                @if (
                    ($serviceDetails->current_status == 'Cancelled' || $serviceDetails->current_status == 'Completed') &&
                        !_quotationExist($serviceDetails->service_id))
                    No quotations found...
                @elseif(hasPermission('leads_quotation', 'view') && _quotationExist($serviceDetails->service_id) && $gp->approved == 1)
                    <h3>Current Quotation</h3>

                    <div class="col-12 mb-1">
                        <div class="resturant-card card--bg-1 position-relative">
                            <h5 class="title" style="font-size:1.1rem;">
                                {{ !$gp->approved ? 'Approval Pending' : ($gp->approved == 1 ? 'Approved' : 'Rejected') }}
                            </h5>
                            <span class="subtitle">Items : </span>

                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Price</th>
                                        <th scope="col">Tax</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($quoteItems as $key => $item)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $item->name }}</td>
                                            <td> {{ \App\CentralLogics\Helpers::currency_symbol() . $item->price }}</td>
                                            <td>{{ $item->tax . '% tax' }}</td>
                                        </tr>
                                    @endforeach

                                </tbody>
                            </table>
                            @php
                                $qSubtotal = collect($quoteItems)->sum(fn($i) => $i->price * $i->qty);
                                $qTax      = collect($quoteItems)->sum(fn($i) => $i->price * $i->qty * ($i->tax / 100));
                                $sym = \App\CentralLogics\Helpers::currency_symbol();
                            @endphp
                            <div class="quote-total-bar mt-2">
                                <div class="qtb-row"><span>Subtotal</span><span>{{ $sym . number_format($qSubtotal, 2) }}</span></div>
                                <div class="qtb-row"><span>Tax</span><span>{{ $sym . number_format($qTax, 2) }}</span></div>
                                <div class="qtb-row qtb-grand"><span>Total</span><span>{{ $sym . number_format($qSubtotal + $qTax, 2) }}</span></div>
                            </div>

                        </div>
                    </div>
                @elseif( hasPermission('leads_quotation', 'edit') &&  _quotationExist($serviceDetails->service_id))
                    <form class="w-100" id="quote_form" enctype="multipart/form-data"
                        action="{{ route('vendor.service.quotations-update') }}" method="post">
                        @csrf
                        <input type="hidden" id="service_id" name="service_id" value="{{ $serviceDetails->service_id }}">
                        @if (_quotationExist($serviceDetails->service_id) && $gp->approved == 2)
                            <h4 class="text-danger">Rejected</h4>
                        @endif

                        <h4 class="m-3 mb-0">Edit Quotation</h4>

                        @if ($gstEnabled)
                            <div class="m-3 d-flex align-items-center gap-3">
                                <label class="mr-3 mb-0 font-weight-600">Tax:</label>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" id="btn-non-gst" class="btn btn-secondary active" onclick="setGstMode(false)">Without GST</button>
                                    <button type="button" id="btn-gst" class="btn btn-outline-secondary" onclick="setGstMode(true)">With GST</button>
                                </div>
                            </div>
                        @endif

                        <table class="table">

                            <th scope="col"><button type="button" class="btn btn-dark btn-sm add_more_btn_mobile"
                                    onclick="addMoreRow()">Add More</button></th>

                            <thead class="" style=" background: #75b8b8; color: white;">
                                <tr>
                                    <th scope="col">Item</th>
                                    <th scope="col">Price <i>(per unit)</i></th>
                                    <th scope="col">Qty</th>
                                    <th scope="col" class="tax-col">Tax <i>(in %)</i></th>

                                    <th scope="col"><button type="button" class="btn btn-dark btn-sm add_more_btn"
                                            onclick="addMoreRow()">Add More</button></th>
                                </tr>
                            </thead>
                            <tbody class="rows_parent">
                                @foreach ($quoteItems as $key => $item)
                                    <tr class="item_row" data-id="{{ $key }}">
                                        <td><input type="text" name="item_name[]" value="{{ $item->name }}"
                                                placeholder="Item Name" class="form-control"></td>
                                        <td><input type="number" name="item_price[]" value="{{ $item->price }}"
                                                placeholder="Price" class="form-control"></td>
                                        <td><input type="number" name="item_qty[]" value="{{ $item->qty }}"
                                                placeholder="Quantity" class="form-control"></td>
                                        <td class="tax-col"><input type="number" name="item_tax[]" value="{{ $item->tax }}"
                                                placeholder="Tax" class="form-control tax-input"></td>
                                        <td><a href="{{ route('vendor.service.delete-quote-item', [$item->id]) }}"
                                                class="btn action-btn btn--danger btn-outline-danger"><i
                                                    class="tio-delete-outlined"></i></a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="quote-total-bar" id="quote-total-bar">
                            <div class="qtb-row"><span>Subtotal</span><span id="qtb-subtotal">—</span></div>
                            <div class="qtb-row" id="qtb-tax-row"><span>Tax</span><span id="qtb-tax">—</span></div>
                            <div class="qtb-row qtb-grand"><span>Total</span><span id="qtb-grand">—</span></div>
                        </div>
                        <div class="form-row  col-12 my-2">
                            <button type="button" class="btn  btn--primary btn-outline-primary submit_btn">Update</button>
                        </div>
                    </form>
                @elseif( hasPermission('leads_quotation', 'add'))
                    <form class="w-100" id="quote_form" enctype="multipart/form-data"
                        action="{{ route('vendor.service.quotations-add') }}" method="post">
                        @csrf
                        <input type="hidden" id="service_id" name="service_id" value="{{ $serviceDetails->service_id }}">
                        <input type="hidden" id="acc_id" name="acc_id" value="{{ $serviceDetails->id }}">

                        <h4 class="m-3 mb-0">Create Quotation</h4>

                        @if ($gstEnabled)
                            <div class="m-3 d-flex align-items-center gap-3">
                                <label class="mr-3 mb-0 font-weight-600">Tax:</label>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" id="btn-non-gst" class="btn btn-secondary active" onclick="setGstMode(false)">Without GST</button>
                                    <button type="button" id="btn-gst" class="btn btn-outline-secondary" onclick="setGstMode(true)">With GST</button>
                                </div>
                            </div>
                        @endif

                        <table class="table">
                            <th scope="col"><button type="button" class="btn btn-dark btn-sm add_more_btn_mobile"
                                    onclick="addMoreRow()">Add More</button></th>

                            <thead class="" style=" background: #75b8b8; color: white;">
                                <tr>
                                    <th scope="col">Item</th>
                                    <th scope="col">Price<i>(per unit)</i></th>
                                    <th scope="col">Qty</th>
                                    <th scope="col" class="tax-col">Tax <i>(in %)</i></th>
                                    <th scope="col"><button type="button" class="btn btn-dark btn-sm add_more_btn"
                                            onclick="addMoreRow()">Add More</button></th>
                                </tr>
                            </thead>
                            <tbody class="rows_parent">
                                <tr class="item_row" data-id="1">
                                    <td><input type="text" name="item_name[]" placeholder="Item Name"
                                            class="form-control"></td>
                                    <td><input type="number" name="item_price[]" placeholder="Price"
                                            class="form-control"></td>
                                    <td><input type="number" name="item_qty[]" placeholder="Quantity"
                                            class="form-control"></td>
                                    <td class="tax-col"><input type="number" name="item_tax[]" placeholder="Tax" class="form-control tax-input" value="0">
                                    </td>
                                    <td><button type="button" onclick="deleteRow(1)"
                                            class="btn action-btn btn--danger btn-outline-danger"><i
                                                class="tio-delete-outlined"></i></button></td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="quote-total-bar" id="quote-total-bar">
                            <div class="qtb-row"><span>Subtotal</span><span id="qtb-subtotal">—</span></div>
                            <div class="qtb-row" id="qtb-tax-row"><span>Tax</span><span id="qtb-tax">—</span></div>
                            <div class="qtb-row qtb-grand"><span>Total</span><span id="qtb-grand">—</span></div>
                        </div>
                        <div class="form-row  col-12 my-2">
                            <button type="button"
                                class="btn  btn--primary btn-outline-primary submit_btn">Create</button>
                        </div>
                    </form>
                @endif

            </div>{{-- end col-md-8 --}}

            {{-- Right sidebar: customer details + quotation preview --}}
            <div class="col-md-4">

                {{-- Customer Details --}}
                @if ($custDet)
                    <h3>Client Details</h3>
                    <div class="col-12 mb-3 p-0">
                        <div class="resturant-card card--bg-3 position-relative">
                            <div class="my-1">
                                <h5 class="title" style="font-size:1.1rem; display:inline;">
                                    {{ $custDet->f_name . ' ' . $custDet->l_name }}
                                </h5>
                            </div>
                            <div class="subtitle my-1">{{ $custDet->email }}</div>
                            <div class="subtitle my-1">{{ $custDet->phone }}</div>
                            @if (!empty($custDet->latitude) && !empty($custDet->longitude))
                                <div class="subtitle my-1">
                                    <a href="https://www.google.com/maps?q={{ $custDet->latitude }},{{ $custDet->longitude }}"
                                        target="_blank">
                                        <i class="fas fa-map-marker-alt mx-1"></i> View Location
                                    </a>
                                </div>
                            @endif
                            <div class="mt-1"><i>Client since: {{ _monthNYear($custDet->created_at) }}</i></div>
                        </div>
                    </div>
                @endif

                {{-- Pending quotation preview --}}
                @if (hasPermission('leads_quotation', 'view') && _quotationExist($serviceDetails->service_id) && !$gp->approved)
                    <h3>Current Quotation</h3>
                    <div class="col-12 mb-1 p-0">
                        <div class="resturant-card card--bg-1 position-relative">
                            <h5 class="title" style="font-size:1.1rem;">Approval Pending</h5>
                            <span class="subtitle">Items:</span>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th>Tax</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($quoteItems as $key => $item)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $item->name }}</td>
                                            <td>{{ \App\CentralLogics\Helpers::currency_symbol() . $item->price }}</td>
                                            <td>{{ $item->qty }}</td>
                                            <td>{{ $item->tax }}% tax</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @php
                                $qSubtotal2 = collect($quoteItems)->sum(fn($i) => $i->price * $i->qty);
                                $qTax2      = collect($quoteItems)->sum(fn($i) => $i->price * $i->qty * ($i->tax / 100));
                                $sym2 = \App\CentralLogics\Helpers::currency_symbol();
                            @endphp
                            <div class="quote-total-bar mt-2">
                                <div class="qtb-row"><span>Subtotal</span><span>{{ $sym2 . number_format($qSubtotal2, 2) }}</span></div>
                                <div class="qtb-row"><span>Tax</span><span>{{ $sym2 . number_format($qTax2, 2) }}</span></div>
                                <div class="qtb-row qtb-grand"><span>Total</span><span>{{ $sym2 . number_format($qSubtotal2 + $qTax2, 2) }}</span></div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>{{-- end col-md-4 --}}
        </div>
        <!-- Resturent Card Wrapper -->

    </div>

@endsection

@push('script_2')
    <script>
        $(document).on('click', '.submit_btn', function() {
            var $itemRows = $('.item_row');
            if ($itemRows.length == 0) {
                toastr.error('Please add atleast one item');
            } else {
                $('#quote_form').submit();
            }
        })

        function deleteRow(rowId) {
            $('[data-id="' + rowId + '"]').remove();
            recalcTotal();
        }

        function addMoreRow() {
            var $lastItemRow = $('.item_row').last();
            var dataId = $lastItemRow.length ? Number($lastItemRow.data('id')) + 1 : 1;

            var taxDisplay = gstMode ? '' : 'style="display:none;"';
            var html = `<tr class="item_row" data-id="` + dataId + `">
                      <td><input type="text" name="item_name[]" placeholder="Item Name" class="form-control"></td>
                      <td><input type="number" name="item_price[]" placeholder="Price" class="form-control"></td>
                      <td><input type="number" name="item_qty[]" placeholder="Quantity" class="form-control"></td>
                      <td class="tax-col" ` + taxDisplay + `><input type="number" name="item_tax[]" placeholder="Tax" class="form-control tax-input" value="0"></td>
                      <td><button type="button" onclick="deleteRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                    </tr>`;

            $('.rows_parent').append(html);
        }

        function recalcTotal() {
            var subtotal = 0, taxTotal = 0;
            $('.item_row').each(function () {
                var price = parseFloat($(this).find('[name="item_price[]"]').val()) || 0;
                var qty   = parseFloat($(this).find('[name="item_qty[]"]').val())   || 0;
                var tax   = parseFloat($(this).find('[name="item_tax[]"]').val())   || 0;
                var line  = price * qty;
                subtotal += line;
                taxTotal += line * (tax / 100);
            });
            var grand = subtotal + taxTotal;
            var sym = '{{ \App\CentralLogics\Helpers::currency_symbol() }}';
            $('#qtb-subtotal').text(sym + subtotal.toFixed(2));
            $('#qtb-tax').text(sym + taxTotal.toFixed(2));
            $('#qtb-grand').text(sym + grand.toFixed(2));
        }

        var gstMode = false;

        function setGstMode(enabled) {
            gstMode = enabled;
            if (enabled) {
                $('#btn-gst').removeClass('btn-outline-secondary').addClass('btn-secondary active');
                $('#btn-non-gst').removeClass('btn-secondary active').addClass('btn-outline-secondary');
                $('.tax-col').show();
                $('#qtb-tax-row').show();
            } else {
                $('#btn-non-gst').removeClass('btn-outline-secondary').addClass('btn-secondary active');
                $('#btn-gst').removeClass('btn-secondary active').addClass('btn-outline-secondary');
                $('.tax-col').hide();
                $('.tax-input').val(0);
                $('#qtb-tax-row').hide();
            }
            recalcTotal();
        }

        $(document).on('input', '[name="item_price[]"], [name="item_qty[]"], [name="item_tax[]"]', recalcTotal);

        $(document).ready(function () {
            setGstMode(false);
            recalcTotal();
        });

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
    </script>
@endpush
