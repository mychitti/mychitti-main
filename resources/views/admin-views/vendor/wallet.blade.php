@extends('layouts.admin.app')

@section('title', 'Wallet')

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('/public/assets/admin/css/intlTelInput.css') }}" />
@endpush



@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/store.png') }}" class="w--26" alt="">
                </span>
                <span>
                    Wallet
                </span>
            </h1>
        </div>
        <div class="card">
            <div class="card-header border-0 pb-0">
                <ul class="nav nav-tabs card-header-tabs" id="walletActionTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="recharge-tab" data-toggle="tab" href="#tab-recharge" role="tab">
                            <i class="tio-add-circle-outlined mr-1"></i> Recharge
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" id="deduct-tab" data-toggle="tab" href="#tab-deduct" role="tab">
                            <i class="tio-remove-circle-outlined mr-1"></i> Deduct
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="walletActionTabsContent">

                    {{-- ── Recharge ── --}}
                    <div class="tab-pane fade show active" id="tab-recharge" role="tabpanel">
                        <form action="{{ route('admin.store.wallet.recharge') }}" class="formSubmitVerify" method="post">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label">Store</label>
                                    <select data-placeholder="Select Store" required name="store_id" id="search_store_id"
                                        class="form-control js-select2-custom">
                                        <option value=""></option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Amount</label>
                                    <input type="number" name="amount" placeholder="Ex: 1200" class="form-control" min="1" step="0.01">
                                </div>
                                <div class="col-md-4 d-flex gap-2 align-items-end pb-1">
                                    <input type="radio" value="1" name="billing" class="billing_status" id="billing" checked>
                                    <label for="billing" class="mb-0">Billing</label>
                                    <input type="radio" value="0" name="billing" class="billing_status" id="retail">
                                    <label for="retail" class="mb-0">Retail</label>
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button class="btn btn-primary">Add To Wallet</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- ── Deduct ── --}}
                    <div class="tab-pane fade" id="tab-deduct" role="tabpanel">
                        <form action="{{ route('admin.store.wallet.deduct') }}" method="POST">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label">Store</label>
                                    <select data-placeholder="Select Store" required name="store_id" id="deduct_store_id"
                                        class="form-control js-select2-custom">
                                        <option value=""></option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" placeholder="Ex: 500" class="form-control" min="0.01" step="0.01" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Service Request ID <span class="text-muted">(optional)</span></label>
                                    <input type="number" name="service_request_id" placeholder="Ex: 1042" class="form-control" min="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                                    <select name="reason" id="deduct_reason" class="form-control" required
                                        data-placeholder="Type or select a reason..."></select>
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-danger"
                                        onclick="return confirm('Deduct this amount from the store wallet?')">
                                        <i class="tio-remove-circle-outlined mr-1"></i> Deduct from Wallet
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{ translate('messages.store_wallet_list') }}<span class="badge badge-soft-dark ml-2"
                            id="itemCount">{{ $storeWallets->total() }}</span></h5>

                    <form class="search-form">
                        <!-- Search -->
                        <div class="input-group input--group">
                            <input type="search" name="search" value="{{ request()?->search ?? null }}"
                                class="form-control min-height-45"
                                placeholder="{{ translate('messages.search_categories') }}"
                                aria-label="{{ translate('messages.ex_:_categories') }}">
                            <input type="hidden" name="position" value="0">
                            <button type="submit" class="btn btn--secondary min-height-45"><i
                                    class="tio-search"></i></button>
                        </div>
                        <!-- End Search -->
                    </form>
                    @if (request()->get('search'))
                        <button type="reset" class="btn btn--primary ml-2 location-reload-to-category"
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

                            <span class="dropdown-header">{{ translate('messages.download_options') }}</span>
                            <a id="export-excel" class="dropdown-item"
                                href="{{ route('admin.category.export-categories', ['type' => 'excel', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                    alt="Image Description">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item"
                                href="{{ route('admin.category.export-categories', ['type' => 'csv', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                    alt="Image Description">
                                .{{ translate('messages.csv') }}
                            </a>

                        </div>
                    </div>
                    <!-- End Unfold -->
                </div>
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
                                <th class="border-0 w--1">{{ translate('messages.store_name') }}</th>
                                <th class="border-0 ">{{ translate('messages.last_recharge') }}</th>
                                <th class="border-0 ">{{ translate('messages.balance') }}</th>
                                <th class="border-0 ">{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>

                        <tbody id="table-div">
                            @foreach ( $storeWallets as $key => $storeWallet)
                                <tr>
                                    <td>{{ $key + $storeWallets->firstItem() }}</td>
                                    <td>
                                        <span class="d-block font-size-sm text-body">
                                            {{ Str::limit($storeWallet->vendorStore?->name, 20, '...') }}
                                        </span>
                                    </td>
                                    <td>
                                       {{$storeWallet->lastRecharge?->amount ?? translate('messages.no_recharge_yet')}}
                                    </td>
                                    <td>
                                        {{_price($storeWallet->total_earning)}}
                                    </td>
                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <a class="btn action-btn btn--primary btn-outline-primary"
                                                href="{{ route('admin.store.wallet.view', [$storeWallet['id']]) }}"
                                                title="{{ translate('messages.view_details') }}"> <i class="tio-visible"></i> 
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if (count($storeWallets) !== 0)
                <hr>
            @endif
            <div class="page-area">
                {!! $storeWallets->appends($_GET)->links() !!}
            </div>
            @if (count($storeWallets) === 0)
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
    <script>
        $(document).ready(function() {
            var storeAjaxOpts = {
                placeholder: 'Search for a store',
                minimumInputLength: 3,
                ajax: {
                    url: "{{ route('admin.store.get-matches') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term }; },
                    processResults: function(data) {
                        return { results: data.map(s => ({ id: s.id, text: s.name + ' (' + s.phone + ')' })) };
                    },
                    cache: true
                }
            };
            $('#search_store_id').select2(storeAjaxOpts);
            $('#deduct_store_id').select2(storeAjaxOpts);

            var reasonAjaxOpts = {
                placeholder: 'Type or select a reason...',
                tags: true,
                minimumInputLength: 0,
                ajax: {
                    url: "{{ route('admin.store.wallet.deduct-reasons') }}",
                    dataType: 'json',
                    delay: 200,
                    data: function(params) { return { q: params.term || '' }; },
                    processResults: function(data) { return data; },
                    cache: true
                },
                createTag: function(params) {
                    var term = $.trim(params.term);
                    if (!term) return null;
                    return { id: term, text: term, newTag: true };
                },
                templateResult: function(data) {
                    if (data.newTag) return $('<span><em>+ Add: </em>' + data.text + '</span>');
                    return data.text;
                }
            };
            $('#deduct_reason').select2(reasonAjaxOpts);
        });

        // Re-init reason select2 when deduct tab is shown (hidden elements issue)
        $('a[data-target="#tab-deduct"]').on('shown.bs.tab', function() {
            $('#deduct_reason').select2($.extend({}, {
                placeholder: 'Type or select a reason...',
                tags: true,
                minimumInputLength: 0,
                ajax: {
                    url: "{{ route('admin.store.wallet.deduct-reasons') }}",
                    dataType: 'json',
                    delay: 200,
                    data: function(params) { return { q: params.term || '' }; },
                    processResults: function(data) { return data; },
                    cache: true
                },
                createTag: function(params) {
                    var term = $.trim(params.term);
                    if (!term) return null;
                    return { id: term, text: term, newTag: true };
                },
                templateResult: function(data) {
                    if (data.newTag) return $('<span><em>+ Add: </em>' + data.text + '</span>');
                    return data.text;
                }
            }));
        });
    </script>
@endpush
