@extends('layouts.vendor.app')

@section('title', 'Staff Salary')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
        }

        @media (max-width: 600px) {

            .table td,
            .table th {
                padding: 2px !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Edit Salary </h1>
            <div class="page-header-select-wrapper">

            </div>
        </div>
        <!-- End Page Header -->


        @if (session()->has('msg'))
            <div class="alert alert-success" role="alert">
                {{ session('msg') }}
            </div>
        @endif

        <div class="row">
            <div class="card w-100 p-0 col-md-9">
                <div class="card-body row py-0 align-items-start">
                    <form action="">
                        <div class="form-row  p-2">
                            <label for="exampleInputEmail1">Month</label>
                            <input onchange="this.form.submit()" type="month" id="start" name="month" min="2018-03"
                                value="{{ $month_year ?? date('Y-m') }}" required class="form-control month_selector ">
                        </div>
                    </form>
                    <div class="form-row col-md-3 p-2">
                        <label for="exampleInputEmail1">Employee</label>
                        @php $emp_info = _getWhere('vendor_employees', ['id' => $salary->ven_id])[0]; @endphp
                        <input type="text" class="form-control" disabled
                            value="{{ $emp_info->f_name . ' ' . $emp_info->l_name . ' #' . $emp_info->id }}" />

                    </div>
                    @if ($empInfo->salary_type == 'Task-Wise')
                        <div class="form-row col-md-3 p-2">
                            <label for="exampleInputEmail1">Total Tasks Amount</label>
                            <input type="number" name="base_salary" readonly value="{{ $data['tasks_amount'] ?? 0 }}"
                                placeholder="Base Salary" class="form-control base_salary">
                            <input type="hidden" name="" value="{{ $empInfo->salary_type }}" class="salary_type">
                        </div>
                    @else
                        <div class="form-row col-md-3 p-2">
                            <label for="exampleInputEmail1">Base Salary
                                {{ $empInfo->salary_type != 'Task-Wise' ? '(' . $empInfo->salary_type . ')' : '' }}</label>
                            <input type="number" name="base_salary" readonly value="{{ $emp_info->base_salary }}"
                                placeholder="Base Salary" class="form-control base_salary">
                            <input type="hidden" name="" value="{{ $empInfo->salary_type }}" class="salary_type">
                        </div>
                    @endif
                    @if ($empInfo->salary_type == 'Monthly')
                        <div class="form-row col-md-3 p-2">
                            <label for="exampleInputEmail1">Vacations / Leaves (days)</label>
                            <input type="number" value="{{ $data['vacation_or_leave'] ?? 0 }}" readonly
                                placeholder="Vacations / Leaves" class="form-control vacation_or_leave">
                        </div>
                    @elseif($empInfo->salary_type == 'Hourly')
                        <div class="form-row col-md-3 p-2">
                            <label for="exampleInputEmail1">Hours Worked</label>
                            <input type="text" value="{{ $data['hours_worked'] ?? 0 }}" readonly
                                placeholder="Hours Worked" class="form-control total_hours">
                        </div>
                    @elseif($empInfo->salary_type == 'Task-Wise')
                        <div class="form-row col-md-3 p-2">
                            <label for="exampleInputEmail1">Tasks Done</label>
                            <input type="text" value="{{ $data['tasks_done'] ?? 0 }}" readonly
                                placeholder="Hours Worked" class="form-control total_tasks">
                        </div>
                    @endif
                </div>
            </div>
            <div class="card w-100 p-0 col-md-3">
                <div class="card-body row py-0">
                    <div class="form-row  p-2 align-items-end">
                        <table>
                            <tr>
                                <td> <span class="fs-2 fw-bold">{{isset($data['tasks_amount']) ? 'Total Task Amount' : 'Base Salary' }}:</span></td>
                                <td>{{ \App\CentralLogics\Helpers::currency_symbol() }}<span
                                        class="base_salary_show">{{ $data['tasks_amount'] ?? $emp_info->base_salary }}</span></td>
                            </tr>
                            <tr>
                                <td> <span class="fs-2 fw-bold">Payable Salary:</span></td>
                                <td>{{ \App\CentralLogics\Helpers::currency_symbol() }}<span
                                        class="payable_salary">0</span></td>
                            </tr>
                            <tr>
                                <td> <span class="fs-2 fw-bold">Allowance:</span></td>
                                <td>{{ \App\CentralLogics\Helpers::currency_symbol() }}<span class="cal_allowance">0</span>
                                </td>
                            </tr>
                            <tr>
                                <td> <span class="fs-2 fw-bold">Bonus:</span></td>
                                <td>{{ \App\CentralLogics\Helpers::currency_symbol() }}<span class="bonus_show">0</span>
                                </td>
                            </tr>
                            <tr>
                                <td> <span class="fs-2 fw-bold">Deductions:</span></td>
                                <td>{{ \App\CentralLogics\Helpers::currency_symbol() }}<span
                                        class="deductions_show">0</span></td>
                            </tr>
                            @if (isset($salary->advance_deductions))
                                <tr>
                                    <td> <span class="fs-2 fw-bold">Advance Payment Deductions:</span></td>
                                    <td>{{ \App\CentralLogics\Helpers::currency_symbol() }}<span
                                            class="advanc_deductions_show">{{ $salary->advance_deductions }}</span>
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <td> <span class="fs-2 fw-bold">Total Payable:</span></td>
                                <td><span
                                        style="font-size:16px; font-weight:bold;">{{ \App\CentralLogics\Helpers::currency_symbol() }}<span
                                            class="payable_total">0</span></span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <form class="w-100 p-0" action="{{ route('vendor.salary.save') }}" method="post">
            @csrf
            <input type="hidden" name="month" value="{{ $month_year ?? date('Y-m') }}">
            <input type="hidden" value="{{ $emp_info->id }}" name="emp_id" />

            <input type="hidden" id="salary_id" name="salary_id" value="{{ $salary->id ?? '' }}">
            <div class="card h-100 mt-2">
                <h4 class="m-2">Basic Details</h4>

                <div class="card-body row py-0 align-items-start">


                    <div class="row g-0 border px-2">
                        <table class="table table-borderless">
                            <thead style="background-color: #ecf1ff">
                                <tr>
                                    <th scope="col">Allowances</th>
                                    <th scope="col"> </th>
                                    <th scope="col" class="p-2">
                                        <button type="button" class="btn btn-dark btn-sm d-none d-md-block"
                                            onclick="addMoreRow()">+</button>
                                        <button type="button" class="btn btn-dark btn-sm d-block d-md-none"
                                            onclick="addMoreRow()"><i class="tio-add-square"></i></button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="rows_parent">
                                @php
                                    if ($salary->allowance) {
                                        $arr = json_decode($salary->allowance);
                                    } else {
                                        $arr = [];
                                    }
                                @endphp
                                @if (!empty($arr))
                                    @foreach ($arr as $key => $ar)
                                        <tr class="item_row" data-id="{{ $key + 1 }}">
                                            <td><input type="text" name="item_name[]" value="{{ $ar->title }}"
                                                    placeholder="Title" class="form-control"></td>
                                            <td><input type="number"  step="0.001" name="item_price[]" value="{{ $ar->amount }}"
                                                    placeholder="Amount" class="form-control item_price"></td>
                                            <td><button type="button" onclick="deleteRow({{ $key + 1 }})"
                                                    class="btn action-btn btn--danger btn-outline-danger"><i
                                                        class="tio-delete-outlined"></i></button></td>
                                        </tr>
                                    @endforeach
                                @else
                                    {{-- <tr class="item_row" data-id="1">
                                                <td><input type="text" name="item_name[]" placeholder="Title"
                                                        class="form-control"></td>
                                                <td><input type="number" name="item_price[]" placeholder="Amount"
                                                        class="form-control item_price"></td>
                                                <td><button type="button" onclick="deleteRow(1)"
                                                        class="btn action-btn btn--danger btn-outline-danger"><i
                                                            class="tio-delete-outlined"></i></button></td>
                                            </tr> --}}
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="form-row col-md-2 p-2">
                        <label for="exampleInputEmail1">Bonus/Incentives</label>
                        <input type="number" value="{{ $salary->bonus_incentives }}"  step="0.001" name="bonus_incentives"
                            placeholder="Bonus/Incentives" class="form-control bonus_incentives">
                    </div>



                    <div class="form-row col-md-2 p-2">
                        <label for="exampleInputEmail1">Other Deductions</label>
                        <input type="number" value="{{ $salary->deductions }}" name="deductions"
                            placeholder="Deductions"  step="0.001" class="form-control deductions">
                    </div>
                    @if (isset($salary->advance_deductions))
                        <div class="form-row col-md-2 p-2">
                            <label for="advance_deductions">Advance Payment Deductions</label>
                            <input type="number"  step="0.001" id="advance_deductions" value="{{ $salary->advance_deductions }}"
                                name="advance_deductions" placeholder="Advance Payment Deductions"
                                class="form-control advance_deductions">
                        </div>
                    @endif

                    <input type="hidden" name="pay_status" value="unpaid">
                    <div class=" col-12 d-flex justify-content-end py-2">

                        <button class="btn btn-primary">Save</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="col-md-12 mt-3">
            <div class="card h-100">
                <div class="row justify-content-between">
                    <h4 class="m-3 mb-0 ">Payment History</h4>
                    <div class="hs-unfold mr-2">
                        <a class="js-hs-unfold-invoker m-2 mx-4 btn btn-white dropdown-toggle min-height-40"
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
                                href="{{ route('vendor.salary.export', ['type' => 'excel', request()->getQueryString(), 'id' => $salary->ven_id]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                    alt="Image Description">
                                {{ translate('messages.excel') }}
                            </a>

                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive datatable-custom">
                        <table id="columnSearchDatatable"
                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                            data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false

                        }'>
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">{{ translate('sl') }}</th>
                                    <th class="border-0">Base Salary</th>
                                    <th class="border-0">Month</th>
                                    <th class="border-0">Deductions</th>
                                    <th class="border-0">Leaves</th>
                                    <th class="border-0">Paid Amount</th>
                                </tr>
                            </thead>


                            <tbody id="set-rows">
                                @foreach ($all_salaries as $lead)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>

                                        <td>
                                            {{ \App\CentralLogics\Helpers::format_currency($lead->base_salary) }}
                                        </td>
                                        <td>
                                            {{ $lead->salary_month }}
                                        </td>
                                        <td>
                                            Rs.{{ $lead->deductions }}
                                        </td>
                                        <td>
                                            {{ $lead->vacation_or_leave }}
                                        </td>

                                        <td>
                                            Rs.{{ number_format($lead->temp_calculated) }}
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if (count($all_salaries))
                            <hr>
                        @else
                            No Previous Record...
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>



@endsection

@push('script_2')
    <script>
        $('#payment_method_selec').on('change', function() {
            if ($(this).val() == 'bank') {
                $('.payment_field').hide();
                $('.payment_field_inp').removeAttr('required');
                $('.bank_field').show();
                $('.bank_field_inp').attr('required', true);
            } else if ($(this).val() == 'upi') {
                $('.payment_field').hide();
                $('.payment_field_inp').removeAttr('required');
                $('.upi_field').show();
                $('.upi_field_inp').attr('required', true);
            } else {
                $('.payment_field_inp').removeAttr('required');
                $('.payment_field').hide();
            }
        })

        $('#emp_select').on('change', function() {

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url: "{{ route('vendor.salary.get-info') }}",
                type: 'POST',
                data: {
                    id: $('#emp_select').val()
                },
                success: function(data) {
                    var info = data.data[0]
                    $('input[name="acc_holder_name"]').val(info.acc_holder_name)
                    $('input[name="account_number"]').val(info.account_number)
                    $('input[name="allowance"]').val(info.allowance)
                    $('input[name="base_salary"]').val(info.base_salary)
                    //   $('input[name="bonus_incentives"]').val(info.bonus_incentives)
                    //   $('input[name="deductions"]').val(info.deductions)
                    $('input[name="ifsc"]').val(info.ifsc)
                    $('input[name="upi_id"]').val(info.upi_id)
                    $('input[name="work_hours"]').val(info.work_hours)

                    $('input[name="payment_method"][value="' + info.payment_method + '"]').prop(
                        'checked', true);


                },
                error: function(err) {
                    showErrorMessage(err);
                }
            });
        })


        function addMoreRow() {

            var $lastItemRow = $('.item_row').last();

            if (!$lastItemRow.length) {
                var dataId = 1;
            } else {
                var dataId = Number($lastItemRow.data('id')) + 1;

            }
            console.log(dataId)

            var html = `<tr  class="item_row" data-id="` + dataId + `">
            <td><input type="text" name="item_name[]" placeholder="Title" class="form-control"></td>
            <td><input type="number" step="0.001" name="item_price[]" placeholder="Amount" class="form-control item_price"></td>
            <td><button type="button"  onclick="deleteRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
            </tr>`;

            $('.rows_parent').append(html)
        }

        function deleteRow(rowId) {
            $('[data-id="' + rowId + '"]').remove()
        }

        function calPayableAMount() {
            console.log('dff')
            var salary_type = $(".salary_type").val(); // 'monthly', 'hourly', 'task_wise'
            var payable_salary = 0;
            var total_allowance = 0;
            var totalPayable = 0;

            var bonus = parseFloat($(".bonus_incentives").val()) || 0;
            var deductions = parseFloat($(".deductions").val()) || 0;
            console.log(deductions)
            console.log('deductions')
            var adv_deductions = parseFloat($("#advance_deductions").val()) || 0;

            $(".advanc_deductions_show").text(adv_deductions);
            $(".deductions_show").text(deductions);
            $(".bonus_show").text(bonus);

            if (salary_type === 'Monthly' ) {
                var base_salary = parseFloat($(".base_salary").val()) || 0;
                var unpaid_leaves = parseFloat($(".vacation_or_leave").val()) || 0;
                var selected_month = $(".month_selector").val() || new Date().toISOString().slice(0, 7);
                var year = parseInt(selected_month.split("-")[0]);
                var month = parseInt(selected_month.split("-")[1]);
                var total_days_in_month = new Date(year, month, 0).getDate();

                var per_day_salary = base_salary / total_days_in_month;
                payable_salary = base_salary - (per_day_salary * unpaid_leaves);

                $('.item_price').each(function() {
                    let value = parseFloat($(this).val()) || 0;
                    let per_day_allowance = value / total_days_in_month;
                    total_allowance += (value - (per_day_allowance * unpaid_leaves));
                });

            } else if (salary_type === 'Hourly') {
                let totalTime = $(".total_hours").val(); // e.g., "10:18"
                let [hours, minutes] = totalTime.split(':').map(Number);

                let total_hours = hours + (minutes / 60);

                let hourly_rate = parseFloat($(".base_salary").val()) || 0;
                payable_salary = hourly_rate * total_hours;

                $('.item_price').each(function() {
                    total_allowance += parseFloat($(this).val()) || 0;
                });

            } else if(salary_type === 'Task-Wise'){
                var base_salary = parseFloat($(".base_salary").val()) || 0;
                payable_salary = base_salary;

                $('.item_price').each(function() {
                    let value = parseFloat($(this).val()) || 0;
                    let per_day_allowance = value / total_days_in_month;
                    total_allowance += (value - (per_day_allowance * unpaid_leaves));
                });  
            }
            totalPayable = payable_salary + total_allowance - deductions - adv_deductions + bonus;

            $(".payable_salary").text(payable_salary.toFixed(3));
            $(".cal_allowance").text(total_allowance.toFixed(3));
            $(".payable_total").text(totalPayable.toFixed(3));
        }

        $(document).ready(function() {
            calPayableAMount()
        })

        $(document).on('change keyup', "#advance_deductions, .item_price, .bonus_incentives, .deductions", function() {
            calPayableAMount();
        });
    </script>
@endpush
