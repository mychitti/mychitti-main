@extends('layouts.vendor.app')

@section('title', _moduleLabel('inventory_manage'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
        }

        .error_inp {
            border: 1px solid red;
        }

        
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Item Entries</h1>
            <div class="page-header-select-wrapper">

            </div>
        </div>
        <!-- End Page Header -->

        <div class="row g-2">

            <form enctype="multipart/form-data" class="w-100" action="{{ route('vendor.inventory.entry.save') }}"
                method="post">
                @csrf
                <div class="col-md-12 px-0">
                    <div class="card h-100">
                        <h3 class="mx-3 mt-2 mb-0">New Entry</h3>
                        <div class="card-body row pt-1">
                            <div class="form-row col-3">
                                <label for="exampleInputEmail1">Date </label>
                                <input type="date" name="date" value="{{ date('Y-m-d') }}" class="form-control">
                            </div>
                            <div class="form-row col-3">
                                <label for="exampleInputEmail1">Bill No.<span class="text-danger">*</span></label>
                                <input type="text" name="bill_number" required placeholder="Bill No."
                                    class="form-control">
                            </div>
                            <div class="form-row col-6">
                                <label for="exampleInputEmail1">Item Name / SKU / Model Number </label>
                                <input type="hidden" id="hidden_inp">
                                <select name="item_id" id="item_id_select" onchange="checkPricing(this)"
                                    class="form-control js-select2-custom">
                                    <option value="">---{{ translate('messages.select') }}---</option>
                                    @foreach ($items as $item)
                                        <option value="{{ $item['id'] }}">
                                            {{ $item['item_name'] . ' | ' . ($item['company_sku_id'] ?? $item['sku_id']) . ' | ' . $item['model_number'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-row col-3">
                                <label for="exampleInputEmail1">MRP<span class="text-danger">*</span></label>
                                <input type="number" name="mrp" id="mrp_inp" onkeyup="checkPricing(this)" required
                                    placeholder="MRP" class="form-control" step="0.001" >
                                <small class="text-danger mrp_error"></small>
                            </div>
                            <div class="form-row col-3">
                                <label for="exampleInputEmail1">GST Rate<span class="text-danger">*</span></label>
                                <input type="number" name="gst_rate" required placeholder="GST Rate" class="form-control">
                            </div>
                            <div class="form-row col-3">
                                <label for="exampleInputEmail1">Quantity<span class="text-danger">*</span></label>
                                <input type="text" name="quantity" required placeholder="Quantity" class="form-control">
                            </div>
                            <div class="form-row col-3">
                                <label for="exampleInputEmail1">Landing Price<span class="text-danger">*</span></label>
                                <input type="number" name="landing_price" onkeyup="checkPricing(this)" id="landing_price"
                                    required placeholder="Landing Price" class="form-control">
                                <small class="text-danger landing_price_error"></small>
                            </div>
                            <div class="form-row col-3">
                                <label for="exampleInputEmail1">Selling Price<span class="text-danger">*</span></label>
                                <input type="number" id="selling_price" readonly placeholder="Calculated Automatically"
                                    class="form-control">
                            </div>
                            <div class="form-row col-3">
                                <label for="exampleInputEmail1">GST Type<span class="text-danger">*</span></label>

                                <select name="gst_type" id="gst_type" class="form-control js-select2-custom">
                                    <option value="">---{{ translate('messages.select') }}---</option>
                                    <option value="cgst_sgst">GST (CGST + SGST)</option>
                                    <option value="igst">IGST</option>
                                    <option value="no_gst">No GST</option>
                                </select>

                            </div>
                            <div class="form-row col-3">
                                <label for="exampleInputEmail1">Taxable Amount<span class="text-danger">*</span></label>
                                <input type="number" id="taxable_amount" name="taxable_amount" placeholder="Ex: 12"
                                    class="form-control">
                            </div>
                            <div class="form-row col-3">
                                <label>Batch Number</label>
                                <input type="text" name="batch_number[]" placeholder="e.g. BT-2024-001" class="form-control">
                            </div>
                            <div class="form-row col-3">
                                <label>Expiry Date</label>
                                <input type="date" name="expiry_date[]" class="form-control">
                            </div>

                            <div class="form-row col-12">
                                <div class="col my-2">
                                    <button class="btn save_btn  btn--primary btn-outline-primary">Save</button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </form>

        </div>
        <div class="row g-2">
            <div class="col-md-12 px-0">
                <div class="card h-100">
                    <h3 class="mx-3 mt-2 mb-0">Entries</h3>
                    <div class="table-responsive datatable-custom" id="table-div">
                        <table id="datatable"
                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">{{ translate('sl') }}</th>
                                    <th class="border-0">Date</th>
                                    <th class="border-0">Company SKU ID</th>
                                    <th class="border-0">SKU ID</th>
                                    <th class="border-0">Bill No.</th>
                                    <th class="border-0">Batch No.</th>
                                    <th class="border-0">Expiry Date</th>
                                    <th class="border-0">Item Name</th>
                                    <th class="border-0">MRP</th>
                                    <th class="border-0 ">Landing Price</th>
                                    <th class="border-0 ">Selling Price</th>
                                    <th class="border-0 ">Quantity</th>
                                    <th class="border-0 ">GST (%)</th>
                                    <th class="border-0 ">GST Amount</th>
                                    <th class="border-0 ">Total</th>
                                    <th class="border-0 ">Created At</th>
                                </tr>
                            </thead>

                            <tbody id="set-rows">
                                @foreach ($entries as $key => $entry)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            {{ $entry->date }}
                                        </td>
                                        <td>
                                            {{ $entry->company_sku_id }}
                                        </td>
                                        <td>
                                            {{ $entry->sku_id }}
                                        </td>
                                        <td>
                                            {{ $entry->bill_number }}
                                        </td>
                                        <td>
                                            {{ $entry->batch_number ?? '—' }}
                                        </td>
                                        <td>
                                            @if($entry->expiry_date)
                                                @php $exp = \Carbon\Carbon::parse($entry->expiry_date); @endphp
                                                <span class="{{ $exp->isPast() ? 'text-danger font-weight-bold' : ($exp->diffInDays(now()) <= 90 ? 'text-warning font-weight-bold' : '') }}">
                                                    {{ $exp->format('M Y') }}
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            {{ $entry->item_name }}
                                        </td>
                                        <td>
                                            {{ \App\CentralLogics\Helpers::format_currency($entry->mrp) }}
                                        </td>
                                        <td>
                                            {{ \App\CentralLogics\Helpers::format_currency($entry->landing_price) }}
                                        </td>
                                        <td>
                                            {{ \App\CentralLogics\Helpers::format_currency($entry->selling_price) }}
                                        </td>
                                        <td>
                                            {{ $entry->quantity }}
                                        </td>
                                        <td>
                                            {{ $entry->gst_percent }}
                                        </td>
                                        <td>
                                            {{ \App\CentralLogics\Helpers::format_currency($entry->gst_amount) }}
                                        </td>
                                        <td>
                                            {{ \App\CentralLogics\Helpers::format_currency($entry->total_amount) }}
                                        </td>
                                        <td>
                                            {{ $entry->created_at }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @if (count($entries) === 0)
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
    </div>

@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>

    <script>
        function checkPricing(elem) {
            var $elem = $(elem);

            // If item is selected
            if ($elem.attr('id') == 'item_id_select') {
                var item_id = $elem.val();

                $.ajax({
                    url: "{{ route('vendor.inventory.get_my_fee_amount') }}",
                    type: "POST",
                    data: {
                        _token: $('[name="_token"]').val(),
                        item_id: item_id,
                    },
                    success: function(resp) {
                        $("#hidden_inp").val(resp);
                        var landing_price = parseFloat($("#landing_price").val()) || 0;
                        var fee = parseFloat(resp) || 0;

                        if (landing_price) {
                            $('#selling_price').val(fee + landing_price);

                        }
                        validateMRP();
                    }
                });
            }

            // If landing price is changed
            if ($elem.attr('id') == 'landing_price' || $elem.attr('id') == 'mrp_inp') {
                var landing_price = parseFloat($("#landing_price").val()) || 0;
                var fee = parseFloat($("#hidden_inp").val()) || 0;

                $('#selling_price').val(fee + landing_price);
                validateMRP();
            }

            function validateMRP() {
                var selling_price = parseFloat($('#selling_price').val()) || 0;
                var mrp = parseFloat($('#mrp_inp').val()) || 0;
                var landing_price = parseFloat($('#landing_price').val()) || 0;

                if (mrp && landing_price && selling_price > mrp) {
                    $(".save_btn").attr('disabled', true);
                    $("#mrp_inp").addClass('error_inp');
                    $("#landing_price").addClass('error_inp');
                    $(".landing_price_error").text('Landing Price cannot be greater than MRP');
                    $(".mrp_error").text('MRP cannot be less than Selling Price');
                } else {
                    $(".save_btn").removeAttr('disabled');
                    $("#mrp_inp").removeClass('error_inp');
                    $("#landing_price").removeClass('error_inp');
                    $(".landing_price_error").text('');
                    $(".mrp_error").text('');
                }
            }
        }
    </script>
@endpush
