@extends('layouts.vendor.app')

@section('title', 'Add Salary')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .form-row {
        margin-top: 6px;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Add Salary </h1>
            <div class="page-header-select-wrapper">

            </div>
        </div>
        <!-- End Page Header -->


        @if (session()->has('msg'))
        <div class="alert alert-success" role="alert">
            {{ session('msg') }}
        </div>
        @endif
        <div class="row g-2">
            <form class="w-100" action="{{ route('vendor.salary.save') }}" method="post">
                @csrf
                <input type="hidden" id="salary_id" name="salary_id" value="">
                <div class="col-md-12">
                    <div class="card h-100">
                        <h4 class="m-3 mb-0">Basic Details</h4>
                        <div class="card-body row">
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Employee <span class="text-danger">*</span></label>
                                <select name="emp_id" required id="emp_select" class="form-control">
                                    <option value="" disabled selected>--select--</option>
                                    @foreach ($employees as $dep)
                                    <option value="{{ $dep->id }}">{{ $dep->f_name . ' '. $dep->l_name . ' #'.$dep->id  }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-row col-4">

                                <label for="exampleInputEmail1">Month<span class="text-danger">*</span></label>
                                <input type="month" id="start" name="month" min="2018-03" value="{{date('Y-m')}}" required placeholder="Base Salary" class="form-control">
                            </div>
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Base Salary<span class="text-danger">*</span></label>
                                <input type="number" name="base_salary"  step="0.001" required placeholder="Base Salary" class="form-control">
                            </div>
                            <!-- <div class="form-row col-4">
                                <label for="exampleInputEmail1">Pay Frequency <span class="text-danger">*</span></label>
                                <select name="pay_frequency" required id="pay_frequency" class="form-control">
                                        <option value="Daily">Daily</option>
                                        <option value="Weekly">Weekly</option>
                                        <option value="Monthly" selected >Monthly</option>
                                </select>
                            </div> -->
                            <!-- <div class="form-row align-items-center ">
                                <label for="exampleInputEmail1">Pay Type<span class="text-danger">*</span></label>
                                <input type="radio" checked style="height:fit-content; margin:5px"  checked name="pay_type" required   value="Salary">
                                <label for="exampleInputEmail1">Salary</label>
                                <input type="radio" style="height:fit-content; margin:5px" name="pay_type" required value="Hourly">
                                <label for="exampleInputEmail1">Hourly</label>
                            </div> -->
                            <!--  <div class="form-row col-4">-->
                            <!--    <label for="exampleInputEmail1">Work Hours<span class="text-danger">*</span></label>-->
                            <!--    <input type="number" name="work_hours" required placeholder="Work Hours" class="form-control">-->
                            <!--</div>-->
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Bonus/Incentives</label>
                                <input type="number" name="bonus_incentives"  step="0.001" placeholder="Bonus/Incentives" class="form-control">
                            </div>
                            
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Vacations / Leaves (days)</label>
                                <input type="number" name="vacation_or_leave" placeholder="Vacations / Leaves" class="form-control">
                            </div>

                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Other Deductions</label>
                                <input type="number" name="deductions"  step="0.001" placeholder="Deductions" class="form-control">
                            </div>

                            
                            <div class="row">
                                <table class="table  table-borderless">
                                    <thead class="">
                                        <tr>
                                            <th scope="col">Allowances</th>
                                            <th scope="col"> </th>
                                            <th scope="col"><button type="button" class="btn btn-dark btn-sm" onclick="addMoreRow()">Add More</button></th>
                                        </tr>
                                    </thead>
                                    <tbody class="rows_parent">
                                        <tr class="item_row" data-id="1">
                                            <td><input type="text" name="item_name[]" placeholder="Title" class="form-control"></td>
                                            <td><input type="number" name="item_price[]"  step="0.001" placeholder="Amount" class="form-control"></td>
                                            <td><button type="button" onclick="deleteRow(1)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="form-row col-4">
                                <div class="w-100">
                                    <label for="exampleInputEmail1">Status</label>
                                    <select name="pay_status" id="" class="form-control">
                                        <option value="" selected disabled>select</option>
                                        <option value="paid">Paid</option>
                                        <option value="unpaid">Unpaid</option>
                                    </select>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 mt-3">
                    <div class="card h-100">
                        <div class="row">
                            <h4 class="m-3 mb-0 col-7">Payment Information</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="form-row col-6">
                                    <label for="inputState">Payment Method<span class="text-danger">*</span></label>
                                    <select id="payment_method_selec" name="payment_method" required id="inputState" class="form-control">
                                        <option value="bank">Bank Transfer</option>
                                        <option value="upi">UPI</option>
                                        <option value="cash">Cash</option>
                                    </select>
                                </div>
                                <div class="form-row col-6 payment_field bank_field">
                                    <label for="inputState">Account Holder Name<span class="text-danger">*</span></label>
                                    <input type="text" required name="acc_holder_name" placeholder="Account Holder Name" class=" form-control  payment_field_inp bank_field_inp">
                                </div>
                                <div class="form-row col-6 payment_field bank_field">
                                    <label for="inputState">Account Number<span class="text-danger">*</span></label>
                                    <input type="number" required name="account_number" placeholder="Account Number" class=" form-control  payment_field_inp bank_field_inp">
                                </div>
                                <div class="form-row col-6  payment_field bank_field">
                                    <label for="inputState">IFSC<span class="text-danger">*</span></label>
                                    <input type="number" required name="ifsc" placeholder="IFSC" class="form-control  payment_field_inp bank_field_inp">
                                </div>
                                <div class="form-row col-6 payment_field upi_field" style="display:none">
                                    <label for="inputState">UPI ID<span class="text-danger">*</span></label>
                                    <input type="number" required name="upi_id" placeholder="UPI ID" class="form-control payment_field_inp upi_field_inp">
                                </div>

                                <div class="form-row col-12">
                                    <div class="col my-2">
                                        <button class="btn  btn--primary btn-outline-primary">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>



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
                            $('#pay_frequency').val(info.pay_frequency);

                            $('input[name="pay_type"][value="' + info.pay_type + '"]').prop('checked', true);
                            $('input[name="payment_method"][value="' + info.payment_method + '"]').prop('checked', true);


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
                     <td><input type="number" name="item_price[]" placeholder="Amount" class="form-control"></td>
                      <td><button type="button"  onclick="deleteRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                   </tr>`;

                    $('.rows_parent').append(html)
                }

                function deleteRow(rowId) {
                    $('[data-id="' + rowId + '"]').remove()
                }
            </script>
            @endpush