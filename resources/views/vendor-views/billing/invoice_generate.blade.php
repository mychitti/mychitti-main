@extends('layouts.vendor.app')

@section('title', 'Generate Bill (Basic)')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (\App\CentralLogics\Helpers::get_store_data()->module_id == 6)
        <style>
            .hidden_hsn {
                display: none;
            }
        </style>
    @endif
    <style>
        /* .select2-results__option:nth-child(2) {
                                color: rgb(13, 96, 252) !important;
                            } */

        .select2-results__option:last-child {
            color: rgb(90, 123, 186) !important;
            font-weight: bold;
        }

        .custom-input {
            padding-left: 0;
            border: 1px solid #e8e6e6;
            box-shadow: none;
            border-left: none;
        }

        .custom-input:focus {
            box-shadow: none;
            border: 1px solid #ececec;
            outline: none;
            border-left: none;
        }

        #totalWithoutGST,
        #totalWithGST,
        .currency {
            font-size: 18px;
            color: black;
        }

        .item_row_inv td {
            padding: 2px !important;
        }

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

        .table th {
            padding: 5px !important;
        }

        #toast {
            visibility: hidden;
            min-width: 250px;
            margin-left: -125px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 16px;
            position: fixed;
            z-index: 1111;
            left: 50%;
            bottom: 30px;
            font-size: 17px;
            opacity: 0;
            transition: opacity 0.5s, bottom 0.5s;
        }

        #toast.show {
            visibility: visible;
            opacity: 1;
            bottom: 50px;
        }
    </style>
@endpush

@section('content')
    <div id="toast" class="toast">This is a toaster notification!</div>

    {{-- @include('vendor-views/sub-module/partials/billing') --}}

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Bill Generate</h1>
            <div class="page-header-select-wrapper">
                @if (hasMasterModulePermission('billing'))
                    @if (hasPermission('billing', 'add_advanced'))
                        <a href="{{ route('vendor.invoice.create-invoice') }}" class="btn btn-sm btn-outline-primary">
                            <i class="tio-document-text mr-1"></i> Generate Advanced Invoice
                        </a>
                    @endif
                @endif
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row g-2">
            @include('vendor-views/forms/basic_invoice')
                    @include('vendor-views.form_modals.inventory_item_select')

        </div>
    </div>
@endsection
@push('script_2')
    @include('vendor-views/billing/basic-inoice-js')
    <script>
        $(document).on('keyup change', ".cash_amount, .online_amount, .price, .qty, .payment_mode", function() {

            validateAmount();

        });

        function validateAmount() {
            if ($("input[name='payment_mode']:checked").val() == 'Cash and Online') {
                var totalAmt = parseFloat($("#totalWithGST").text().replace(/,/g, '')) || 0;

                var cash = parseFloat($(".cash_amount").val()) || 0;
                var online = parseFloat($(".online_amount").val()) || 0;

                var entered = cash + online;

                if (entered > totalAmt) {
                    $(".amount_error").text("Amount is greater than total!");
                    $(".submit_btn").attr('disabled', true)
                } else if (entered < totalAmt) {
                    $(".amount_error").text("Amount is less than total!");
                    $(".submit_btn").prop("disabled", true);
                } else {
                    $(".amount_error").text("");
                    $(".submit_btn").removeAttr("disabled");
                }
            } else {
                $(".amount_error").text("");
                $(".submit_btn").removeAttr("disabled");
            }
        }
    </script>
@endpush
