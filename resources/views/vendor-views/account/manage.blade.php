@extends('layouts.vendor.app')

@section('title', 'Add Staff')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
           .form-row{
            margin-top: 6px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Add Staff </h1>
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
            <form class="w-100" action="{{ route('vendor.staff.save') }}" method="post">
                @csrf
                <input type="hidden" id="staff_id" name="staff_id" value="{{isset($staff->id ) ? $staff->id : ''}}">
                <div class="col-md-12">
                    <div class="card h-100">
                        <h4 class="m-3 mb-0">Personal Information</h4>
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
                                <label for="exampleInputEmail1">Base Salary<span class="text-danger">*</span></label>
                                <input type="number" name="base_salary" step="0.001" required placeholder="Base Salary" class="form-control">
                            </div>
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Pay Frequency <span class="text-danger">*</span></label>
                                <select name="pay_frequency" required id="" class="form-control">
                                        <option value="Daily">Daily</option>
                                        <option value="Weekly">Weekly</option>
                                        <option value="Monthly" selected >Monthly</option>
                                </select>
                            </div>
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Pay Type<span class="text-danger">*</span></label>
                                <input type="radio" checked name="pay_type" required  value="salary">
                                <label for="exampleInputEmail1">Salary<span class="text-danger">*</span></label>
                                <input type="radio" name="pay_type" required value="hourly">
                                <label for="exampleInputEmail1">Hourly<span class="text-danger">*</span></label>
                            </div>
                            <div class="form-row col-6">
                                <label for="exampleInputEmail1">Bonus/Incentives</label>
                                <input type="number" name="bonus_incentives"  step="0.001" placeholder="Bonus/Incentives" class="form-control">
                            </div>
                            <div class="form-row col-6">
                                <label for="exampleInputEmail1">Allowances</label>
                                <input type="number" name="allowance"  step="0.001" placeholder="Allowances" class="form-control">
                            </div>
                            <div class="form-row col-6">
                                <label for="exampleInputEmail1">Deductions</label>
                                <input type="number" name="deductions" step="0.001"  placeholder="Deductions" class="form-control">
                            </div>
                            <div class="form-row col-6">
                                <label for="exampleInputEmail1">Work Hours</label>
                                <input type="number" name="work_hours" required placeholder="Work Hours" class="form-control">
                            </div>
                            <div class="form-row col-6">
                                <label for="exampleInputEmail1">Vacations / Leaves (days)</label>
                                <input type="number" name="vacation_or_leave" required placeholder="Absence" class="form-control">
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
                                    <label for="inputState">Payment Method</label>
                                    <select id="payment_method_selec" name="department_id" required id="inputState" class="form-control">
                                        <option value="bank">Bank Transfer</option>
                                        <option value="upi">UPI</option>
                                        <option value="cash">Cash</option>
                                    </select>
                                </div>
                                <div class="form-row col-6 payment_field bank_field">
                                    <label for="inputState">Account Holder Name</label>
                                    <input type="text" required name="acc_holder_name" placeholder="Account Holder Name" class="form-control">
                                </div>
                                <div class="form-row col-6 payment_field bank_field">
                                    <label for="inputState">Account Number</label>
                                    <input type="number" required name="account_number" placeholder="Account Number" class="form-control">
                                </div>
                                <div class="form-row col-6 payment_field bank_field">
                                    <label for="inputState">IFSC</label>
                                    <input type="number" required name="ifsc" placeholder="IFSC" class="form-control">
                                </div>
                                <div class="form-row col-6 payment_field upi_field">
                                    <label for="inputState">UPI ID</label>
                                    <input type="number" required name="upi_id" placeholder="UPI ID" class="form-control">
                                </div>
                              
                                <div class="form-row">
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
            $('#payment_method_selec').on('change', function (){
                if($(this).val() == 'bank'){
                    $('.payment_field').hide();
                    $('.payment_field').removeAttr('required');
                    $('.bank_field').show();
                    $('.bank_field').attr('required', true);
                }else if($(this).val()== 'upi'){
                    $('.payment_field').hide();
                    $('.payment_field').removeAttr('required');
                    $('.upi_field').show();
                    $('.upi_field').attr('required', true);
                }else{
                    $('.payment_field').removeAttr('required');
                    $('.payment_field').hide();
                }
            })

            $('#emp_select').on('change', function(){
                $.ajax({
                    url: "{{ route('vendor.salary.get-info', ':id') }}".replace(':id', $('#emp_select').val()),
                    type: 'GET',
                    success: function (data) {
                       consol.log(data)
                    },
                    error: function (err) {
                        showErrorMessage(err);
                    }
                });
            })
        </script>
        @endpush
