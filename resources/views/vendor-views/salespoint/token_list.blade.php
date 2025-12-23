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
        <div class="card mt-3">
            <div class="card-header py-2 border-0 flex-wrap d-flex gap-1">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{ translate('messages.POS_tokens') }}<span class="badge badge-soft-dark ml-2"
                            id="itemCount">{{ $tokens->total() }}</span></h5>
                </div>
                @if (hasPermission('pos_token', 'export'))
                    <a href="{{ route('vendor.pos.token.export') }}" class="btn btn-outline-primary mx-1">Export</a>
                @endif

                <form action="" class="d-flex date-range-form gap-1">
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
                    <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning mx-1" type="button"
                        data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>

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
                                <th class="border-0">Token Number</th>
                                <th class="border-0">Client</th>
                                <th class="border-0 ">Total</th>
                                <th class="border-0 ">Payment Method</th>
                                <th class="border-0 ">Payment Status</th>
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
                                        @if ($token->payment_method == 'cash')
                                            <span class="badge badge-primary">Cash</span>
                                        @elseif($token->payment_method == 'card')
                                            <span class="badge badge-success">Card</span>
                                        @else
                                            <span class="badge badge-info">UPI</span>
                                        @endif
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

@endsection

@push('script_2')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
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
