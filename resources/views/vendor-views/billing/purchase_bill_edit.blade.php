@extends('layouts.vendor.app')

@section('title', translate('messages.Edit Purchase Bill'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header flex-wrap align-items-center d-flex justify-content-between">
            <h1 class="page-header-title text-capitalize">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/order.png') }}" class="w--26" alt="">
                </span>
                <span>
                    Edit Purchase Bill
                    <span class="badge badge-soft-dark ml-2">{{ $invoice->invoice_id }}</span>
                </span>
            </h1>
            <a href="{{ route('vendor.invoice.my-bills') }}" class="btn btn_sm btn--primary">
                <i class="tio-back-ui"></i> Back to Purchase Bills
            </a>
        </div>

        @include('vendor-views.forms.purchase_bill_edit')
    </div>

    @include('vendor-views.form_modals.inventory_item_select')
@endsection

@push('script_2')
    @include('vendor-views.billing.create_invoice_js')
    <script>
        $(function() {
            recalculateInvoice();
            calculateTotals();
            updateEmptyState();
        });
    </script>
@endpush
