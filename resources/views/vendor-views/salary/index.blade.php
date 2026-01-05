@extends('layouts.vendor.app')

@section('title', 'Salary List')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')

    {{-- @include('vendor-views/sub-module/partials/salary') --}}

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Salary <span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($salary) }}</span></h1>
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Salary List
                        {{ isset($month_year) ? date('F Y', strtotime($month_year)) : date('F Y') }}</h5>
                    <div class="d-flex align-items-start" style="gap:5px;">
                        @if (isset($salary[0]) && $salary[0]->generated_at)
                            @if (hasPermission('salary_manage', 'mark_paid'))
                                <a href="{{ route('vendor.salary.mark-paid', [$month_year ?? date('Y-m')]) }}"
                                    class="btn btn_sm btn-outline-warning">Mark Paid</a>
                            @endif
                            @if (hasPermission('salary_manage', 'export'))
                                <a href="{{ route('vendor.salary.export-salaries', [$month_year ?? date('Y-m')]) }}"
                                    class="btn btn_sm btn-outline-success">Export</a>
                            @endif
                        @else
                            @if (hasPermission('salary_manage', 'generate'))
                                <a href="{{ route('vendor.salary.generate-monthly', [$month_year ?? date('Y-m')]) }}"
                                    class="btn btn_sm btn-outline-success">{{ isset($salary[0]) && $salary[0]->generated_at ? 'Regenerate' : 'Generate' }}
                                    Salaries</a>
                            @endif
                        @endif
                        <form action="">
                            <input onchange="this.form.submit()" type="month" value="{{ $month_year ?? date('Y-m') }}"
                                name="month" class="form-control input_sm" id="">
                    </div>
                    </form>
                </div>
            </div>
            <!-- End Header -->
            @if (hasPermission('salary_manage', 'list'))
                <!-- Table -->
                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable"
                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                        data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false

                        }'>
                        @php
                            $selectedMonth = $month_year ?? date('Y-m');
                            $currentMonth = date('Y-m');
                        @endphp
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('sl') }}</th>
                                <th class="border-0">Employee</th>
                                <th class="border-0">Base Salary</th>
                                <th class="border-0">Salary Type</th>
                                <th class="border-0">Payable Salary</th>
                                <th class="border-0">Bonus</th>
                                <th class="border-0">Allowance</th>
                                <th class="border-0">Deductions</th>
                                <th class="border-0">Advance Pay Deductions</th>
                                <th class="border-0">Payable Total</th>
                                <th class="border-0">Pay Status</th>
                                <th class="text-center border-0">{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                            @foreach ($salary as $key => $lead)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div>
                                            @php
                                            $empInfo = _getWhereOne('vendor_employees', [
                                                                                                'id' => $lead->ven_id,
                                                                                    ]); @endphp 
                                            <a href="{{ hasPermission('staff_manage', 'view') ? route('vendor.employee.view', [$empInfo?->id]) : '#' }}"
                                                class="table-rest-info" alt="view store">

                                                @php

                                                    if (isset($empInfo)) {
                                                        echo $empInfo->f_name . ' ' . $empInfo->l_name;
                                                } @endphp

                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($empInfo->salary_type == 'Task-Wise')
                                            -
                                        @else
                                            {{ _price($lead->base_salary ?? $empInfo->base_salary) }}
                                        @endif
                                    </td>
                                    <td>
                                        {{ $empInfo->salary_type }}
                                    </td>
                                    <td>
                                        {{ _price($lead->payable_salary) }}
                                    </td>
                                    <td>
                                        {{ _price($lead->bonus_incentives) }}
                                    </td>
                                    <td>
                                        {{ _price($lead->allowance_amount) }}
                                    </td>
                                    <td>
                                        {{ _price($lead->deductions) }}
                                    </td>
                                    <td>
                                        {{ _price($lead->advance_payment_deductions) }}
                                    </td>
                                    <td>
                                        {{ _price($lead->total_payable) }}
                                    </td>
                                    <td>
                                        @if ($lead->pay_status == 'paid')
                                            <span class="badge badge-soft-success">Paid
                                            </span>
                                        @else
                                            <span class="badge badge-soft-danger">Unpaid
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="btn--container justify-content-center">
                                            @if ($selectedMonth !== $currentMonth)
                                                <a class="btn action-btn btn--primary btn-outline-primary"
                                                    href="{{ route('vendor.salary.edit', [$lead->ven_id]) }}?month={{ $month_year ?? date('Y-m') }}"title="{{ translate('messages.edit') }} Salary"><i
                                                        class="tio-edit"></i>
                                                </a>
                                            @endif

                                            @if (hasPermission('salary_manage', 'mark_paid') && $lead->id && $lead->pay_status != 'paid')
                                                <a data-toggle="modal" data-target="#markPaidModal{{ $key }}"
                                                    style="min-width:fit-content; padding:0px 5px;"
                                                    class="btn action-btn btn--primary btn-outline-primary" type="button"
                                                    title="Pay Salary">Mark Paid
                                                </a>
                                                <div class="modal fade" id="markPaidModal{{ $key }}"
                                                    tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="exampleModalLabel">Document
                                                                    Upload
                                                                    (Optional)
                                                                </h5>
                                                                <button type="button" class="close" data-dismiss="modal"
                                                                    aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form enctype="multipart/form-data"
                                                                    action="{{ route('vendor.salary.pay') }}"
                                                                    method="post">
                                                                    @csrf
                                                                    <input type="hidden" value="{{ $lead->id }}"
                                                                        name="salary_id">
                                                                    <label for="">Document (Optional)</label>
                                                                    <input type="file" name="file" id=""
                                                                        class="form-control">
                                                                    <button
                                                                        class="btn btn-primary float-right">Proceed</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @elseif(hasPermission('salary_manage', 'view_document') && $lead->pay_reciept)
                                                <a target="_blank" style="min-width:fit-content; padding:0px 5px;"
                                                    class="btn btn-outline-warning action-btn"
                                                    href="{{ asset('storage/app/public/vendor/documents/') . '/' . $lead->pay_reciept }}">View
                                                    Documemt</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if (count($salary))
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
                <!-- End Table -->
            @endif
        </div>
        <!-- End Card -->
    </div>

@endsection

@push('script_2')
    <script>
        function status_change_alert(url, message, e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: 'No',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = url;
                }
            })
        }
        $(document).on('ready', function() {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            var datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

            $('#column1_search').on('keyup', function() {
                datatable
                    .columns(1)
                    .search(this.value)
                    .draw();
            });

            $('#column2_search').on('keyup', function() {
                datatable
                    .columns(2)
                    .search(this.value)
                    .draw();
            });

            $('#column3_search').on('keyup', function() {
                datatable
                    .columns(3)
                    .search(this.value)
                    .draw();
            });

            $('#column4_search').on('keyup', function() {
                datatable
                    .columns(4)
                    .search(this.value)
                    .draw();
            });


            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function() {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>
@endpush
