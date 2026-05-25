@extends('layouts.admin.app')

@section('title', 'Add Quotation')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
        <style>
            .hidden_hsn {
                display: none;
            }
        </style>
    <style>
      .item_row_quote td {
            padding: 2px !important;
        }
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

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex align-items-center justify-content-between">
                <h1 class="page-header-title mb-0"><i class="tio-filter-list"></i> Quotation Add</h1>
                @if(isset($fromQuery) && $fromQuery)
                    <a href="{{ route('admin.sales-crm.query.show', $fromQuery->id) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="tio-arrow-backward mr-1"></i> Back to Query {{ $fromQuery->ref_no }}
                    </a>
                @endif
            </div>
        </div>
        <!-- End Page Header -->

        @if(isset($fromQuery) && $fromQuery)
        <div class="alert alert-soft-info d-flex align-items-center mb-3" style="border-left:4px solid #377dff; background:#f0f5ff;">
            <i class="tio-info-outined mr-2" style="font-size:1.2rem;color:#377dff;"></i>
            <span>Creating quotation for Sales Query <strong>{{ $fromQuery->ref_no }}</strong> — <strong>{{ $fromQuery->contact_name }}</strong>
            @if($fromQuery->company) ({{ $fromQuery->company }}) @endif</span>
        </div>
        @endif

        <div class="row g-2">
            @include('admin-views/forms/quote_add', [
                'fromQuery'           => $fromQuery ?? null,
                'preselectedCustomer' => $preselectedCustomer ?? null,
            ])
            @include('admin-views.form_modals.inventory_item_select')
        </div>
    </div>
@endsection
@push('script_2')
    @include('admin-views/quote/quote-js')

    @if(isset($preselectedCustomer) && $preselectedCustomer)
    <script>
    $(document).ready(function () {
        // Activate the mychitti_client panel (shows correct select, sets name="bill_to")
        $('#quoteRadioMychitti').trigger('change');

        var label = '{{ addslashes($preselectedCustomer->f_name) }}'
                  + '{{ $preselectedCustomer->phone ? " (" . $preselectedCustomer->phone . ")" : "" }}';
        var option = new Option(label, '{{ $preselectedCustomer->id }}', true, true);
        $('#quote_mychitti_select').append(option).trigger('change');
    });
    </script>
    @endif
@endpush
