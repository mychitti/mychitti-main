@extends('layouts.vendor.app')

@section('title', translate('messages.My Bills'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid pb-1">
        <!-- Page Header -->
        <div class="page-header flex-wrap align-items-center d-flex justify-content-between">
            <h1 class="page-header-title text-capitalize">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/order.png') }}" class="w--26" alt="">
                </span>
                <span>
                    My Bills
                    <span class="badge badge-soft-dark ml-2">{{ $bills->total() }}</span>
                </span>
            </h1>
            <div class="d-flex">
                @if (hasPermission('purchase_bill', 'add'))
                    <button class="btn btn_sm btn--primary mx-1" data-toggle="modal" data-target="#purchaseBillModal">Add
                        Purchase
                        Bill</button>
                @endif
                @if (hasPermission('purchase_bill', 'import'))
                    <button data-toggle="modal" data-target="#importExcelModal" class="btn btn_sm btn--primary">Import
                        Excel</button>
                @endif
            </div>
        </div>
    </div>
    <!-- End Page Header -->
                {{-- @if (hasPermission('purchase_bill', 'list')) --}}

    <!-- Card -->
    <div class="card">
        <!-- Header -->
        <div class="card-header py-2 border-0">
            <div class="search--button-wrapper justify-content-end">

            </div>
            <!-- End Row -->
        </div>
        <!-- End Header -->
        <div class="card-body p-0">
            <!-- Table -->
            <div class="table-responsive datatable-custom">
                <table id="datatable"
                    class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">
                                {{ translate('messages.#') }}
                            </th>
                            <th class="border-0 table-column-pl-0">{{ translate('messages.invoice_id') }}</th>
                            <th class="border-0">{{ translate('messages.total_amount') }}</th>
                            <th class="border-0">{{ translate('messages.payment_method') }}</th>
                            <th class="border-0 ">{{ translate('messages.due_by') }}</th>
                            <th class="border-0 ">{{ translate('messages.ref. File') }}</th>
                            <th class="border-0 ">{{ translate('messages.action') }}</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach ($bills as $key => $order)
                            <tr class="status-{{ $order['order_status'] }} class-all">
                                <td class="">
                                    {{ $key + $bills->firstItem() }}
                                </td>
                                <td class="table-column-pl-0">
                                     <a href="{{  $order['pdf'] ? asset('storage/invoice/') . '/'  .  $order['pdf'] : 'javascript:;'}}" target="_blank">{{ $order['invoice_id'] }}</a>   
                                </td>
                                <td>
                                    <div class=" mw--85px">
                                        <div>
                                            {{ _price($order['total_amount']) }}
                                        </div>
                                        @if ($order->payment_status == 'Paid')
                                            <strong class="text-success">
                                                {{ translate('messages.paid') }}
                                            </strong>
                                        @else
                                            <strong class="text-danger">
                                                {{ translate('messages.unpaid') }}
                                            </strong>
                                        @endif
                                    </div>
                                </td>

                                <td class="text-capitalize">

                                    <span class="badge badge-soft-info">
                                        {{ $order['payment_method'] }}
                                    </span>
                                </td>
                                <td class="text-capitalize ">

                                    {{ $order['payment_date'] }}

                                </td>
                                <td class="text-capitalize ">
                                    @php
                                        $file = $order->reference_file;
                                        $filePath = asset('storage/app/public/store/docs/' . $file);
                                        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                    @endphp

                                    @if ($file)
                                        @if (in_array($ext, $imageExtensions))
                                            <a href="{{ $filePath }}" target="_blank">
                                                <img src="{{ $filePath }}" alt="Reference Image"
                                                    style="max-width: 50px; height: auto; border: 1px solid #ccc;">
                                            </a>
                                        @else
                                            <a href="{{ $filePath }}" target="_blank">View File</a>
                                        @endif
                                    @endif

                                </td>
                                <td>
                                    <div class="btn--container ">
                                       @if (hasPermission('purchase_bill', 'view'))
                                            @if ($order['pdf'])
                                                <a class="btn btn-sm btn--primary btn-outline-primary action-btn"
                                                    target="_blank"
                                                    href="{{ asset('storage/app/public/invoice') . '/' . $order['pdf'] }}"><i
                                                        class="tio-visible"></i></a>
                                            @else
                                                <a class="btn btn-sm btn--primary btn-outline-primary action-btn"
                                                    target="_blank"
                                                    href="{{ route('vendor.invoice.manual-invoice-view', ['manual', $order['invoice_id']]) }}?store"><i
                                                        class="tio-visible"></i></a>
                                            @endif
                                        @endif
                                       @if (hasPermission('purchase_bill', 'delete'))
                                            <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                href="javascript:" data-id="category-{{ $order['id'] }}"
                                                data-message="{{ translate('Want to delete this bill') }}"
                                                title="{{ translate('messages.delete_bill') }}"><i
                                                    class="tio-delete-outlined"></i>
                                            </a>
                                            <form action="{{ route('vendor.invoice.purchase-bill.delete', [$order['id']]) }}"
                                                method="get" id="category-{{ $order['id'] }}">
                                                @csrf @method('get')
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if (count($bills) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif
            </div>
            <!-- End Table -->
        </div>
        <!-- Footer -->
        <div class="card-footer">
            {!! $bills->links() !!}
        </div>
        <!-- End Footer -->
    </div>
    {{-- @endif --}}
    <!-- End Card -->

    <div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Import Excel</h5>
                    <button type="button" class="close close_modal" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="importForm" action="{{ route('vendor.invoice.purchase-invoice.import') }}" method="post"
                        enctype="multipart/form-data">
                        @csrf
                        <a href="{{ asset('storage/app/public/util/purchase-bulk-bills.xlsx') }}" download
                            class="btn btn-outline-primary mb-2">View Example</a>
                        <div class="form-group">
                            <label for="file">Upload Excel File</label>
                            <input type="file" name="file" class="form-control" id="file" accept=".xlsx,.xls">
                        </div>
                        <div class="form-group w-100 ">
                            <button type="submit" class="btn btn-primary float-right">Import</button>
                        </div>
                    </form>
                </div>
                <div class="p-3">
                    <h4>How It Works</h4>
                    <ol>
                        <li>Download Example Sheet. Fill you data in it or create new file with exact same headings.</li>
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
                        <li><strong>Vendor Id</strong><br>
                            Enter vendor id (e.g., <code> 23</code>).
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


    @include('vendor-views/form_modals/purchase_bill')
    @include('vendor-views.form_modals.inventory_item_select')

@endsection

@push('script_2')
    <script>
        $("#store_id").select2();
    </script>

    @include('vendor-views.billing.create_invoice_js')
@endpush
