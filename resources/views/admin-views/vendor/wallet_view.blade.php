@extends('layouts.admin.app')

@section('title', 'Wallet Transactions')

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('/public/assets/admin/css/intlTelInput.css') }}" />
@endpush



@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap gap-3 align-items-center">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/store.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ $wallet->vendorStore?->name }} Wallet
                </span>
            </h1>
            <div class="rounded bg-soft-primary px-3 py-2">
                <span>Balance : {{ _price($wallet->total_earning) }}</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger ml-auto"
                data-toggle="modal" data-target="#deductModal">
                <i class="tio-remove-circle-outlined mr-1"></i> Deduct Amount
            </button>
        </div>

        {{-- Deduct Modal --}}
        <div class="modal fade" id="deductModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" style="max-width:460px;" role="document">
                <div class="modal-content" style="border-radius:12px;">
                    <div class="modal-header" style="border-bottom:1px solid #f0f0f0;">
                        <h5 class="modal-title" style="font-size:14px;font-weight:700;">
                            Deduct from Wallet &mdash; {{ $wallet->vendorStore?->name }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form action="{{ route('admin.store.wallet.deduct') }}" method="POST">
                        @csrf
                        <input type="hidden" name="store_id" value="{{ $wallet->vendorStore?->id }}">
                        <div class="modal-body">
                            <div class="alert alert-soft-warning d-flex align-items-center gap-2 mb-3" style="font-size:12px;">
                                <i class="tio-info-outined" style="font-size:16px;"></i>
                                Available balance: <strong>{{ _price($wallet->balance) }}</strong>
                            </div>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" class="form-control" placeholder="0.00"
                                        min="0.01" max="{{ $wallet->balance }}" step="0.01" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Service Request ID <span class="text-muted">(optional)</span></label>
                                    <input type="number" name="service_request_id" class="form-control" placeholder="SR #" min="1">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                                    <select name="reason" id="deduct_reason_modal" class="form-control" required
                                        data-placeholder="Type or select a reason..."></select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top:1px solid #f0f0f0;">
                            <button type="button" class="btn btn-white" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger"
                                onclick="return confirm('Confirm deduction from this store\'s wallet?')">
                                Deduct Amount
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{ translate('messages.transaction_list') }}<span class="badge badge-soft-dark ml-2"
                            id="itemCount">{{ $transactions->total() }}</span></h5>



                    <form class="search-form">
                        <!-- Search -->
                        <div class="input-group input--group">
                            <input type="search" name="search" value="{{ request()?->search ?? null }}"
                                class="form-control min-height-45"
                                placeholder="{{ translate('messages.search_reason_or_amount') }}"
                                aria-label="{{ translate('messages.ex_:_reasons') }}">
                            <input type="hidden" name="position" value="0">
                            <button type="submit" class="btn btn--secondary min-height-45"><i
                                    class="tio-search"></i></button>
                        </div>
                        <!-- End Search -->
                    </form>
                    @if (request()->get('search'))
                        <a href="{{ url()->current() }}" class="btn btn--primary ml-2">
                            {{ translate('messages.reset') }}
                        </a>
                    @endif

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
                                <th class="border-0 text-center">{{ translate('messages.amount') }}</th>
                                <th class="border-0 text-center">{{ translate('messages.type') }}</th>
                                <th class="border-0 text-center">{{ translate('messages.reason') }}</th>
                                <th class="border-0 text-center">{{ translate('messages.done_by') }}</th>
                            </tr>
                        </thead>

                        <tbody id="table-div">
                            @foreach ($transactions as $key => $transaction)
                                <tr>
                                    <td>{{ $key + $transactions->firstItem() }}</td>
                                    <td>
                                        <span class="d-block font-size-sm text-body">
                                            {{ Str::limit($wallet->vendorStore?->name, 20, '...') }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ _price($transaction->amount) }}
                                    </td>
                                    <td>
                                        @if ($transaction->action == 'credit')
                                            <span class="badge badge-soft-success">
                                                {{ translate('messages.credit') }}
                                            </span>
                                        @else
                                            <span class="badge badge-soft-danger">
                                                {{ translate('messages.debit') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $transaction->reason }}</td>
                                    <td>{{ $transaction->created_by }}</td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if (count($transactions) !== 0)
                <hr>
            @endif
            <div class="page-area">
                {!! $transactions->appends($_GET)->links() !!}
            </div>
            @if (count($transactions) === 0)
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
    var reasonOpts = {
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

    $('#deductModal').on('shown.bs.modal', function() {
        $('#deduct_reason_modal').select2($.extend({}, reasonOpts, { dropdownParent: $('#deductModal') }));
    });
});
</script>
@endpush
