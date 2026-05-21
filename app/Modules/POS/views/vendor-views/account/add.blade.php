@extends('layouts.vendor.app')

@section('title', 'Master Ledger Book')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
        }

        /* Journal Entry Modal */
        .vendor-header {
            background: white;
            border-bottom: 1px solid #ddd;
            padding: 15px 0;
            margin-bottom: 20px;
        }

        .vendor-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 0;
            {{-- text-align: center; --}}
        }

        .vendor-address {
            color: #666;
            font-size: 12px;
            {{-- text-align: center; --}} margin: 5px 0 0 0;
            line-height: 1.3;
        }

        .logo-container {
            {{-- position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%); --}}
        }

        .company-logo {
            {{-- width: 60px;
            height: 60px; --}} background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #666;
            font-weight: bold;
        }

        .form-container {
            background: white;
            padding: 30px;
        }

        .card {
            border: none;
            box-shadow: none;
        }

        .form-control,
        .form-control {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }

        .text-danger {
            color: #dc3545 !important;
        }



        @media (max-width: 768px) {
            .header-title {
                font-size: 1.5rem;
            }

            .header-icon {
                font-size: 2rem;
            }

            .form-container {
                padding: 20px;
            }
        }

        .row_clickable {
            cursor: pointer;
        }

        .row_clickable:hover {
            background: #e7fffdff;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap justify-content-between align-items-center w-100">
            <div>
                <h1 class="page-header-title"><i class="tio-filter-list"></i> Master Ledger Book</h1>
            </div>
            <div class="page-header-select-wrapper d-flex gap-2 flex-wrap">
                <form action="" class=" date-range-form">
                    @include('vendor-views/form_modals/date_range')
                    <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning" type="button"
                        data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>
                </form>
                @if (hasPermission('boa_master_ledger', 'add'))
                    <button type="button" class="btn btn-primary ledger_entry_btn btn_sm " data-toggle="modal"
                        data-target="#masterLedgerEntryModal">
                        + Add Expense
                    </button>
                @endif
                {{-- <button type="button" class="btn btn-primary btn_sm  add_acc_btn" type="button" data-id=""
                    data-name="" data-toggle="modal" data-target="#subAccAddModal">
                    + Add Account
                </button> --}}
                @if (hasPermission('boa_master_ledger', 'export'))
                    <a href="{{ route('vendor.account.master-ledger.export') }}" class="btn btn_sm btn-outline-primary ">
                        Export
                    </a>
                @endif
                {{-- @if (hasPermission('boa_master_ledger', 'import'))
                    <a data-toggle="modal" data-target="#importExcelModal" class="btn btn-outline-primary btn_sm">Import</a>
                @endif --}}
            </div>
        </div>
        <!-- End Page Header -->
        @if (hasPermission('boa_master_ledger', 'list'))
            <div class="table-responsive datatable-custom">
                <table id="myTable"
                    class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                    data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false

                        }'>
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">{{ translate('sl') }}</th>
                            <th class="border-0">Date</th>
                            <th class="border-0">Account</th>
                            <th class="border-0">Debit</th>
                            <th class="border-0">Credit</th>
                            <th class="border-0">Narration</th>
                            <th class="border-0">Voucher No.</th>
                            <th class="border-0">Status</th>
                            <th class="text-center border-0">{{ translate('messages.action') }}</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach ($ledger_entries as $entry)
                            <tr class="row_clickable" data-id="{{ $entry->id }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <span class="d-block font-size-sm text-body">
                                        {{ $entry->entry_date }}
                                    </span>
                                </td>
                                <td>
                                    {{ $entry->account?->name }}
                                </td>
                                <td>
                                    @if ($entry->debit > 0)
                                        <span class="text-danger fs-1 fw-bold" style="font-size: 14px ; font-weight: bold;">
                                            {{ _price($entry->debit, null, 3) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($entry->credit > 0)
                                        <span class="text-success fs-1 fw-bold"
                                            style="font-size: 14px ; font-weight: bold;">
                                            {{ _price($entry->credit, null, 3) }}
                                        </span>
                                    @endif
                                </td>
                                </td>
                                <td>
                                    {{ $entry->voucher?->narration }}
                                </td>
                                <td>
                                    {{ $entry->voucher?->voucher_number }}
                                </td>

                                <td>

                                    @if ($entry->status == 'pending')
                                        <span class="badge badge-soft-warning">
                                            {{ translate('messages.pending') }}
                                        </span>
                                    @elseif($entry->status == 'rejected')
                                        <span class="badge badge-soft-danger">
                                            {{ translate('messages.rejected') }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-success">
                                            {{ translate('messages.approved') }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{-- @if (!$entry->has_debit && $entry->credit > 0)
                                    <button style="padding: 0 10px !important; width: fit-content;" data-toggle="modal"
                                        data-target="#masterLedgerEntryModal" data-voucher_id="{{ $entry->voucher?->id }}"
                                        data-amount="{{ $entry->debit > 0 ? $entry->debit : $entry->credit }}"
                                        data-voucher_number = "{{ $entry->voucher?->voucher_number }}"
                                        class="btn action-btn btn-outline-danger opp_entry">+ Debit Entry</button>
                                @endif

                                @if (!$entry->has_credit && $entry->debit > 0)
                                    <button style="padding: 0 10px !important; width: fit-content;" data-toggle="modal"
                                        data-target="#masterLedgerEntryModal" data-voucher_id="{{ $entry->voucher?->id }}"
                                        data-amount="{{ $entry->debit > 0 ? $entry->debit : $entry->credit }}"
                                        data-voucher_number = "{{ $entry->voucher?->voucher_number }}"
                                        class="btn action-btn btn-outline-success opp_entry">+ Credit Entry</button>
                                @endif --}}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if (count($ledger_entries))
                    <hr>
                @else
                    <div class="page-area">
                    </div>
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif
            </div>
        @endif
    </div>
        @if (hasPermission('boa_master_ledger', 'import'))

    <!-- Button trigger modal -->
    <div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Import Excel</h5>
                    <button type="button" class="close close_modal" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('vendor.account.master-ledger.import') }}" method="post"
                        enctype="multipart/form-data">
                        @csrf
                        <a href="{{ asset('storage/app/public/util/master_ledger_example.xlsx') }}" download
                            class="btn btn-outline-primary mb-2">View Example</a>
                        <div class="form-group">
                            <label for="file">Upload Excel File</label>
                            <input type="file" style="height: 46px !important;" name="file" class="form-control"
                                id="file" accept=".xlsx,.xls">
                        </div>
                        <div class="form-group w-100 ">
                            <button type="submit" class="btn btn-primary float-right">Import</button>
                        </div>
                    </form>
                </div>
                <div class="p-3">
                    <h4>How It Works</h4>
                    <ol>
                        <li><span class="text-danger"> Do not edit or delete column headings.</span></li>
                        <li><strong>Temp Group</strong><br> Use a short code like <code>TMP1</code>, <code>TMP2</code> or
                            <code>1</code>, <code>2</code>
                            to group entries of the same voucher.<br> Both rows belonging to the same voucher must have the
                            same Temp Voucher Id.
                        </li>
                        <li><strong>Required Fields</strong><br> Must fill : <code>Ledger Account Id</code>, <code>Credit /
                                Debit</code>
                            <br> Ids can be found in <code>Accounts Management > Settings > Chart of Accounts</code>
                        </li>

                        <li><strong>Credit / Debit Entry</strong><br>There should be at least one entry for
                            <code>Credit</code> and one for <code>Debit</code> for each voucher
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    @endif
        @if (hasPermission('boa_master_ledger', 'add'))

    <div class="modal fade" id="masterLedgerEntryModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header pt-0">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    @include('vendor-views.forms.master_ledger_entry')
                    <!-- Vendor Header Section -->

                </div>
            </div>
        </div>
    </div>
    @endif
    
    <div class="modal fade" id="entryDetailModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header pt-0">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">

                </div>
            </div>
        </div>
    </div>

    @include('vendor-views.form_modals.store_sub_account_add')

@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
    <script>
        @if ($tab == 'add')
            $(".ledger_entry_btn").click()
        @endif

        $('#myTable tr').click(function() {
            if ($(event.target).hasClass('opp_entry')) {
                return; // exit the click handler
            }
            var rowId = $(this).data('id');

            // Fetch details via AJAX
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
            });
            $.post({
                url: "{{ route('vendor.account.master-ledger.get-entry-details') }}",

                data: {
                    id: rowId
                },
                beforeSend: function() {},
                success: function(data) {
                    console.log(data)
                    $('#entryDetailModal .modal-body').html(data);
                    $('#entryDetailModal').modal('show');
                }
            })


        });

        $(".opp_entry").on('click', function() {
            let voucherId = $(this).attr('data-voucher_id');
            let voucherNumber = $(this).attr('data-voucher_number');
            let amount = $(this).attr('data-amount');
            $(".voucher_number").val(voucherId);
            $(".voucher_number_show").text(voucherNumber);
            $(".amount_inp").val(amount).attr('readonly', true);
        })
        $('.add_acc_btn').on('click', function(e) {
            $('#subAccAddModal').modal('show');

            var name = $(this).attr('data-name')
            var id = $(this).attr('data-id')
            var level = $(this).attr('data-level')
            console.log('fd')
            if (level == 1) {
                $(".account_type").show()
            } else {
                $(".account_type").hide()
            }
            $(".parent_id").val(id)
            $(".parent_account_heading").text(name)
            $(".parent_account_show").text(name)


        });

        $("#category").on('change', function() {
            const selectedValue = $('#category option:selected').data('value');
            const selectedText = $('#category option:selected').data('text');
            const selectedType = $('#category option:selected').data('type');
            // Update account type display
            $(".acc_type_show")
                .text(selectedType.toUpperCase())
                .removeClass('text-success text-danger')
                .addClass(selectedType === 'credit' ? 'text-success' : 'text-danger');

            // Determine opposite type for customer
            let customerType = selectedType === 'credit' ? 'debit' : 'credit';

            $(".customer_acc_type_show")
                .text(customerType.toUpperCase())
                .removeClass('text-success text-danger')
                .addClass(customerType === 'credit' ? 'text-success' : 'text-danger');


            $(".ledger_account_type").val(selectedText)

            $("#ledger_account_type2").val(selectedValue).trigger('change');
        });
        $("#customer_id").on('change', function() {
            if ($(this).val() == 'add_new') {
                $('#addCustomerModal').modal('show')
            }
        })

        $(".customer_add_form").on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData($(this).get(0));
            formData.append('form_type', 'ajax');
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    if (data.status) {
                        $('#addCustomerModal').modal('hide')
                        toasterNotification(data.msg)
                        // Suppose your AJAX returns new customer details in data.customer
                        let newOption = new Option(
                            data.customer.f_name + ' (' + data.customer.phone + ')',
                            data.customer.id,
                            true, // selected
                            true // defaultSelected
                        );

                        // Append new option to select
                        $('#customer_id').append(newOption).trigger('change');


                    } else if (data.errors) {
                        for (let i = 0; i < data.errors.length; i++) {
                            toasterNotification(data.errors[i])

                        }
                    }
                }
            });
        });
    </script>
    @include('vendor-views/js/date_range')
@endpush
