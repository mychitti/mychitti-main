@extends('layouts.vendor.app')

@section('title', translate('messages.Tokens List'))

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <style>
        .dropdown-toggle:not(.dropdown-toggle-empty)::after {
            display: none;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/category.png') }}" class="w--20" alt="">
                </span>
                <span>
                    {{ translate('POS_tokens') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar avatar-sm bg-primary text-white rounded-circle"
                                style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                                <i class="tio-money"></i>
                            </span>
                            <div>
                                <span class="text-muted d-block" style="font-size:12px;">Total Amount</span>
                                <h4 class="mb-0">{{ _price($totalAmount ?? 0) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar avatar-sm bg-success text-white rounded-circle"
                                style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                                <i class="tio-receipt-outlined"></i>
                            </span>
                            <div>
                                <span class="text-muted d-block" style="font-size:12px;">Total Tokens</span>
                                <h4 class="mb-0">{{ $tokens->total() }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header py-2 border-0 flex-wrap d-flex gap-1">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{ translate('messages.POS_tokens') }}<span class="badge badge-soft-dark ml-2"
                            id="itemCount">{{ $tokens->total() }}</span></h5>
                </div>
                @if (hasPermission('pos_token', 'export'))
                    <a href="{{ route('vendor.pos.token.export', request()->query()) }}"
                        class="btn btn-outline-primary mx-1">
                        Export
                    </a>
                @endif


                <form action="" class="d-flex date-range-form gap-1">
                    <select class="form-control mx-1 js-select2-custom" name="payment_method" onchange="this.form.submit()">
                        <option {{ request()->payment_method == 'all' ? 'selected' : '' }} value="all">All Payment
                            Methods
                        </option>
                        <option {{ request()->payment_method == 'cash' ? 'selected' : '' }} value="cash">Cash</option>
                        <option {{ request()->payment_method == 'bank' ? 'selected' : '' }} value="bank">Bank</option>
                        <option {{ request()->payment_method == 'upi' ? 'selected' : '' }} value="upi">UPI</option>
                    </select>

                    @if (!empty($upiAccounts) && $upiAccounts->count() > 0)
                        <select class="form-control mx-1 js-select2-custom" name="upi_account_id"
                            onchange="this.form.submit()">
                            <option value="all"
                                {{ request()->upi_account_id == 'all' || !request()->upi_account_id ? 'selected' : '' }}>
                                All UPI IDs</option>
                            @foreach ($upiAccounts as $upi)
                                <option value="{{ $upi->id }}"
                                    {{ request()->upi_account_id == $upi->id ? 'selected' : '' }}>
                                    {{ $upi->upi_id }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    @if (auth('vendor')->check())
                        <select class="form-control mx-1 js-select2-custom" name="branch" onchange="this.form.submit()">
                            <option {{ request()->branch == 'all' ? 'selected' : '' }} value="all">All Branches
                            </option>
                            @foreach ($branches as $key => $branch)
                                <option {{ request()->branch == $branch->id ? 'selected' : '' }}
                                    value="{{ $branch->id }}">{{ ucfirst($branch->name) }}</option>
                            @endforeach
                        </select>
                    @endif
                    <select class="form-control mx-1 js-select2-custom" name="staff_id" onchange="this.form.submit()">
                        <option value="all"
                            {{ request()->staff_id == 'all' || !request()->staff_id ? 'selected' : '' }}>All Staff</option>
                        <option value="0" {{ request()->staff_id === '0' ? 'selected' : '' }}>Self</option>
                        @foreach ($staffList as $staff)
                            <option value="{{ $staff->id }}" {{ request()->staff_id == $staff->id ? 'selected' : '' }}>
                                {{ $staff->name }}
                            </option>
                        @endforeach
                    </select>
                    <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning mx-1"
                        type="button" data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>

                    {{-- date range modal --}}
                    @include('vendor-views/form_modals/date_range')


                </form>
                <form action="" class="h-100">
                    <!-- Search -->
                    <div class="input-group input--group" style="    flex-wrap: nowrap !important; ">
                        <input type="search" style="min-width:210px;height: 100%;     padding: 11px 10px;" name="search"
                            value="{{ request()?->search ?? null }}" class="form-control "
                            placeholder="{{ translate('messages.search by token number') }}">
                        <button type="submit" class="btn btn--secondary "><i class="tio-search"></i></button>
                    </div>
                    <!-- End Search -->
                </form>

                @if (hasPermission('pos_token', 'generate'))
                    <a href="{{ route('vendor.pos.token') }}" class="btn btn--primary mx-1">+ Generate Token</a>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable" class="table table-borderless table-thead-bordered table-align-middle"
                        data-hs-datatables-options='{
                            "isResponsive": false,
                            "isShowPaging": false,
                            "paging":false,
                        }'>
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('sl') }}</th>
                                <th class="border-0 ">Token Number</th>
                                <th class="border-0 ">Client</th>
                                <th class="border-0 ">Total</th>
                                <th class="border-0 ">Payment Method</th>
                                <th class="border-0 ">UPI ID</th>
                                <th class="border-0 ">Payment Status</th>
                                <th class="border-0 ">Staff</th>
                                <th class="border-0 ">Order Type</th>
                                <th class="border-0 ">Created At</th>
                                <th class="border-0 ">Action</th>
                            </tr>
                        </thead>

                        <tbody id="table-div">
                            @foreach ($tokens as $key => $token)
                                <tr>
                                <tr>
                                    <td>{{ $key + $tokens->firstItem() }}</td>
                                    <td>{{ $token->token_number }}</td>
                                    <td>
                                        @if ($token->client)
                                            <a
                                                href="{{ route('vendor.customer.view', [$token->client->id]) }}">{{ $token->client?->f_name }}</a>
                                        @elseif($token->customer_id === 0)
                                            Walk-in
                                        @endif

                                    </td>
                                    <td>
                                        {{ _price($token->total) }}
                                    </td>
                                    <td>
                                        <a type="button" class="edit_token" data-id="{{ $token->id }}"
                                            data-method="{{ $token->payment_method }}" data-toggle="modal"
                                            data-target="#paymentEditModal">
                                            @if ($token->payment_method == 'cash')
                                                <span class="badge badge-primary">Cash</span>
                                            @elseif($token->payment_method == 'card')
                                                <span class="badge badge-success">Card</span>
                                            @elseif($token->payment_method == 'bank')
                                                <span class="badge badge-success">Bank</span>
                                            @elseif($token->payment_method == 'upi')
                                                <span class="badge badge-info">UPI</span>
                                            @else
                                                <span
                                                    class="badge badge-info">{{ ucfirst($token->payment_method) }}</span>
                                            @endif
                                        </a>
                                    </td>
                                    <td>
                                        {{ $token->upiAccount->upi_id ?? '—' }}
                                    </td>
                                    <td>
                                        @if ($token->payment_status == 'paid')
                                            <span class="badge badge-soft-success">Paid</span>
                                        @elseif($token->payment_status == 'unpaid')
                                            @if (hasPermission('pos_token', 'mark_paid') && $token->payment_status == 'unpaid')
                                                <a class="btn btn_sm btn-outline-danger form-alert" href="javascript:"
                                                    data-id="mark-paid-{{ $token->id }}"
                                                    style="    font-size: 11px;
                                                padding: 4px 21px;
                                                background: #ffecec !important;"
                                                    data-message="{{ translate('Want to mark paid this token') }}"
                                                    title="{{ translate('messages.mark paid') }}"> Unpaid
                                                </a>
                                            @else
                                                <span class="badge badge-soft-danger">Unpaid</span>
                                            @endif
                                        @else
                                            <span class="badge badge-soft-danger">Cancelled</span>
                                        @endif
                                    </td>
                                    <td>{{ $token->staff_id == 0 ? 'Self' : $token->staff->name ?? '—' }}</td>
                                    <td>{{ ucfirst($token->order_from) }}</td>
                                    <td>{{ $token->created_at }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn p-1 dropdown-toggle" type="button" data-toggle="dropdown"
                                                aria-expanded="false">
                                                <i class="fa-solid fa-bars"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                @if (hasPermission('pos_token', 'mark_paid') && $token->payment_status == 'unpaid')
                                                    <a class="dropdown-item text-success form-alert" href="javascript:"
                                                        data-id="mark-paid-{{ $token->id }}"
                                                        data-message="{{ translate('Want to mark paid this token') }}"
                                                        title="{{ translate('messages.mark paid') }}"><i
                                                            class="fa-solid fa-check-double"></i> Mark Paid
                                                    </a>
                                                    <form action="{{ route('vendor.pos.token.mark-paid', [$token->id]) }}"
                                                        method="get" id="mark-paid-{{ $token->id }}">
                                                        @csrf @method('get')
                                                    </form>
                                                @endif
                                                @if ($token->invoice)
                                                    <a href="{{ asset('storage/app/public/invoice') . '/' . $token->invoice->pdf }}"
                                                        class="dropdown-item  text-primary"
                                                        title="{{ translate('messages.view') }}"><i
                                                            class="tio-visible"></i>
                                                        View Invoice
                                                    </a>
                                                @else
                                                    @if (hasPermission('pos_token', 'convert to invoice'))
                                                        <a class="dropdown-item  text--warning "
                                                            href="{{ route('vendor.pos.token.convert-to-bill', [$token->id]) }}"
                                                            title="PDF"><i class="fa-solid fa-money-bill-transfer"></i>
                                                            Convert to
                                                            Invoice
                                                        </a>
                                                    @endif
                                                @endif
                                                @if ($token->pdf)
                                                    <a class="dropdown-item  text--primary "
                                                        href="{{ asset('storage/app/public/store/tokens/') . '/' . $token->pdf }}"
                                                        title="PDF"><i class="tio-document-text"></i> Token PDF
                                                    </a>
                                                @endif

                                                @if (hasPermission('pos_token', 'cancel'))
                                                    <a class="dropdown-item text-danger form-alert" href="javascript:"
                                                        data-id="cancel-{{ $token->id }}"
                                                        data-message="{{ translate('Want to cancel this token') }}"
                                                        title="{{ translate('messages.remove item') }}"><i
                                                            class="fa-solid fa-ban"></i> Cancel
                                                    </a>
                                                    <form action="{{ route('vendor.pos.token.cancel', [$token->id]) }}"
                                                        method="get" id="cancel-{{ $token->id }}">
                                                        @csrf @method('get')
                                                    </form>
                                                @endif
                                                @if (hasPermission('pos_token', 'delete'))
                                                    <a class="dropdown-item text-danger form-alert" href="javascript:"
                                                        data-id="category-{{ $token->id }}"
                                                        data-message="{{ translate('Want to remove this item from pos') }}"
                                                        title="{{ translate('messages.remove item') }}"><i
                                                            class="tio-delete-outlined"></i> Delete
                                                    </a>
                                                    <form action="{{ route('vendor.pos.token.delete', [$token->id]) }}"
                                                        method="get" id="category-{{ $token->id }}">
                                                        @csrf @method('get')
                                                    </form>
                                                @endif
                                            </div>

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if (count($tokens) !== 0)
                <hr>
            @endif
            <div class="page-area">
                {!! $tokens->appends(request()->query())->links() !!}

            </div>
            @if (count($tokens) === 0)
                <div class="empty--data">
                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                    <h5>
                        {{ translate('no_data_found') }}
                    </h5>
                </div>
            @endif
        </div>

    </div>

    <div class="modal fade" id="paymentEditModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Payment Mehtod</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="{{ route('vendor.pos.token.payment-method') }}">
                    @csrf
                    <input type="hidden" name="token_id" class="edit_token_id">
                    <div class="modal-body">
                        <div class="pos--payment-options ">
                            <ul style="flex-wrap: nowrap;">
                                <li>
                                    <label>
                                        <input type="radio" name="payment_method" class="payment_method"
                                            value="cash" hidden>
                                        <span class="size_span">Cash</span>
                                    </label>
                                </li>
                                <li>
                                    <label>
                                        <input type="radio" name="payment_method" class="payment_method"
                                            value="card" hidden>
                                        <span class="size_span">Card</span>
                                    </label>
                                </li>
                                <li>
                                    <label>
                                        <input type="radio" name="payment_method" class="payment_method"
                                            value="upi" hidden>
                                        <span class="size_span">UPI</span>
                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(".edit_token").on('click', function() {
            var id = $(this).data('id')
            var method = $(this).data('method')

            $('.edit_token_id').val(id)
            $('.payment_method[value="' + method + '"]').prop('checked', true);
        })
        $(document).on('change', '.item_type', function() {
            if ($(this).val() === 'inv_item') {
                $('#itemDiv').show();
                $('#serviceDiv').hide();
            } else {
                $('#itemDiv').hide();
                $('#serviceDiv').show();
            }
        });
        $('#branches').off('change').on('change', function() {
            let selectedIds = $(this).val() || []; // Always unique IDs
            let container = $('#branch-prices');

            // Store previous prices
            let existingPrices = {};
            container.find('input').each(function() {
                existingPrices[$(this).data('branch-id')] = $(this).val();
            });

            // Clear container
            container.empty();

            // Append only selected branch price inputs
            selectedIds.forEach(function(branchId) {
                let branchName = $('#branches option[value="' + branchId + '"]').text();
                let priceValue = existingPrices[branchId] || '';

                container.append(`
            <div class="branch-price-item" data-branch-id="${branchId}">
                <label>${branchName} Price:</label>
                <input type="number" 
                       name="prices[${branchId}]" 
                       data-branch-id="${branchId}"
                       placeholder="Enter price" 
                       step="0.001" 
                       value="${priceValue}"
                       class="form-control">
            </div>
        `);
            });
        });
        $(document).ready(function() {
            $('#branches').select2({
                tags: true, // Enable new tag creation
                tokenSeparators: [','], // Allow splitting on commas/spaces
                placeholder: "Select or type branches"
            });
        });

        $(document).ready(function() {

            $('.js-example-tags').select2({
                tags: true,
                tokenSeparators: [','],
                placeholder: "Start Typing..",
                allowClear: true,
            });
        });
    </script>

    <script>
        $(document).on('change', '.warnig-charge-btn', function(e) {

            $('.warnig-charge-btn').prop('checked', false);
            swal({
                //text: message,
                title: 'Please Add Lead Charges First',
                type: 'warning',
                confirmButtonColor: '#FC6A57',
                confirmButtonText: 'Add Now',
            }).then(function(isConfirm) {
                console.log(isConfirm)
                if (isConfirm.value) {
                    window.location.href = '{{ route('admin.service.lead-charge') }}';
                } else {


                }
            })

        })
    </script>

    <script src="{{ asset('public/assets/admin') }}/js/view-pages/category-index.js"></script>
    <script>
        "use strict";
        $('.location-reload-to-category').on('click', function() {
            const url = $(this).data('url');
            let nurl = new URL(url);
            nurl.searchParams.delete('search');
            location.href = nurl;
        });

        $("#customFileEg1").change(function() {
            readURL(this);
            $('#viewer').show(1000)
        });
        $('#reset_btn').click(function() {
            $('#exampleFormControlSelect1').val(null).trigger('change');
            $('#viewer').attr('src', "{{ asset('public/assets/admin/img/upload-img.png') }}");
        })
    </script>
    @include('vendor-views/js/date_range')
@endpush
