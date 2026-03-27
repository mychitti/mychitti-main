@extends('layouts.admin.app')

@section('title', 'Edit Salary')

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
        <div class="row g-2">
            <form class="w-100" action="{{ route('admin.users.salary.save') }}" method="post">
                @csrf
                <input type="hidden" id="salary_id" name="salary_id" value="{{$salary->id}}">
                <div class="col-md-12">
                    <div class="card h-100">
                        <h4 class="m-3 mb-0">Basic Details</h4>
                        <div class="card-body row">
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Employee <span class="text-danger">*</span></label>
                                @php $emp_info = _getWhere('admins', ['id' => $salary->employee_id])[0]; @endphp
                                <input type="text" class="form-control" disabled value="{{$emp_info->f_name. ' ' . $emp_info->l_name . ' #' .$salary->employee_id ;}}"/>
                                <input type="hidden" value="{{$salary->employee_id}}" name="emp_id" />
                              
                            </div>
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Base Salary<span class="text-danger">*</span></label>
                                <input type="number" name="base_salary"  step="0.001" required value="{{$salary->base_salary}}" placeholder="Base Salary" class="form-control">
                            </div>
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Pay Frequency <span class="text-danger">*</span></label>
                                <select name="pay_frequency" required id="pay_frequency" class="form-control">
                                        <option value="Daily" {{$salary->pay_frequency == 'Daily' ? 'selected' : ''}}>Daily</option>
                                        <option value="Weekly" {{$salary->pay_frequency == 'Weekly' ? 'selected' : ''}}>Weekly</option>
                                        <option value="Monthly" {{$salary->pay_frequency == 'Monthly' ? 'selected' : ''}}  >Monthly</option>
                                </select>
                            </div>
                            <div class="form-row col-4 align-items-center">
                                <label for="exampleInputEmail1">Pay Type<span class="text-danger">*</span></label>
                                <input type="radio" {{$salary->pay_type == 'Salary' ? 'checked' : ''}} style="height:fit-content; margin:5px"  checked name="pay_type" required   value="Salary">
                                <label for="exampleInputEmail1">Salary</label>
                                <input type="radio" {{$salary->pay_type == 'Hourly' ? 'checked' : ''}} style="height:fit-content; margin:5px" name="pay_type" required value="Hourly">
                                <label for="exampleInputEmail1">Hourly</label>
                            </div>
                            <!--  <div class="form-row col-4">-->
                            <!--    <label for="exampleInputEmail1">Work Hours<span class="text-danger">*</span></label>-->
                            <!--    <input type="number" value="{{$salary->work_hours}}" name="work_hours" required placeholder="Work Hours" class="form-control">-->
                            <!--</div>-->
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Bonus/Incentives</label>
                                <input type="number" value="{{$salary->bonus_incentives}}" name="bonus_incentives"  step="0.001" placeholder="Bonus/Incentives" class="form-control">
                            </div>
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Allowances</label>
                                <input type="number" value="{{$salary->allowance}}" name="allowance"  step="0.001" placeholder="Allowances" class="form-control">
                            </div>
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Deductions</label>
                                <input type="number" value="{{$salary->deductions}}" name="deductions"  step="0.001"  placeholder="Deductions" class="form-control">
                            </div>
                          
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Vacations / Leaves (days)</label>
                                <input type="number" value="{{$salary->vacation_or_leave}}" name="vacation_or_leave"  placeholder="Vacations / Leaves" class="form-control">
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
                                        <option {{$salary->payment_method == 'bank' ? 'selected' : ''}} value="bank">Bank Transfer</option>
                                        <option {{$salary->payment_method == 'upi' ? 'selected' : ''}} value="upi">UPI</option>
                                        <option {{$salary->payment_method == 'cash' ? 'selected' : ''}} value="cash">Cash</option>
                                    </select>
                                </div>
                                <div class="form-row col-6 payment_field bank_field" {{($salary->payment_method == 'upi' ||  $salary->payment_method == 'cash') ? 'style=display:none;' : ''}}>
                                    <label for="inputState">Account Holder Name<span class="text-danger">*</span></label>
                                    <input type="text" value="{{$salary->acc_holder_name}}" {{($salary->payment_method == 'upo' ||  $salary->payment_method == 'cash') ? '' : 'required'}} name="acc_holder_name" placeholder="Account Holder Name" class=" form-control  payment_field_inp bank_field_inp">
                                </div>
                                <div class="form-row col-6 payment_field bank_field" {{($salary->payment_method == 'upi' ||  $salary->payment_method == 'cash') ? 'style=display:none;' : ''}}>
                                    <label for="inputState">Account Number<span class="text-danger">*</span></label>
                                    <input type="number" value="{{$salary->account_number}}" {{($salary->payment_method == 'upo' ||  $salary->payment_method == 'cash') ? '' : 'required'}} name="account_number" placeholder="Account Number" class=" form-control  payment_field_inp bank_field_inp">
                                </div>
                                <div class="form-row col-6  payment_field bank_field" {{($salary->payment_method == 'upi' ||  $salary->payment_method == 'cash') ? 'style=display:none;' : ''}}>
                                    <label for="inputState">IFSC<span class="text-danger">*</span></label>
                                    <input type="number" value="{{$salary->ifsc}}" {{($salary->payment_method == 'upo' ||  $salary->payment_method == 'cash') ? '' : 'required'}} name="ifsc" placeholder="IFSC" class="form-control  payment_field_inp bank_field_inp">
                                </div>
                                <div class="form-row col-6 payment_field upi_field" {{($salary->payment_method == 'bank' ||  $salary->payment_method == 'cash') ? 'style=display:none;' : ''}}>
                                    <label for="inputState">UPI ID<span class="text-danger">*</span></label>
                                    <input type="number" value="{{$salary->upi_id}}" {{($salary->payment_method == 'bank' ||  $salary->payment_method == 'cash') ? '' : 'required'}} name="upi_id" placeholder="UPI ID" class="form-control payment_field_inp upi_field_inp">
                                </div>
                              
                                <div class="form-row col-12">
                                    <div class="col my-2">
                                        <button class="btn  btn--primary btn-outline-primary">Update</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="col-md-12 mt-3">
                    <div class="card h-100">
                        <div class="row">
                            <h4 class="m-3 mb-0 col-7">Payment History</h4>
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
             

                    <tbody id="set-rows">
                        @foreach ($all_salaries as $lead)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div>
                                        <a href="{{route('admin.users.salary.edit',[$lead['id']])}}" class="table-rest-info" alt="view store">

                                            <div class="info">
                                                <div class="text--title">
                                                @php 
                                        $depNm = _getWhere('admins', ['id'=> $lead->employee_id]);
                                        if(isset( $depNm[0])){ echo $depNm[0]->f_name . ' ' . $depNm[0]->l_name . ' #'. $lead->employee_id ; } @endphp
                                                </div>
                                               
                                            </div>
                                        </a>
                                    </div>
                                </td>
                                <td>
                               {{\App\CentralLogics\Helpers::format_currency($lead->base_salary)}}
                               </td>
                                <td>
                                    {{ $lead->pay_frequency }}
                                </td>
                                <td>
                                  {{ $lead->work_hours }}
                                </td>
                               
                             
                                <td>
                                   {{ $lead->created_at }}
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



        @endsection

        @push('script_2')
        <script>
            $('#payment_method_selec').on('change', function (){
                if($(this).val() == 'bank'){
                    $('.payment_field').hide();
                    $('.payment_field_inp').removeAttr('required');
                    $('.bank_field').show();
                    $('.bank_field_inp').attr('required', true);
                }else if($(this).val()== 'upi'){
                    $('.payment_field').hide();
                    $('.payment_field_inp').removeAttr('required');
                    $('.upi_field').show();
                    $('.upi_field_inp').attr('required', true);
                }else{
                    $('.payment_field_inp').removeAttr('required');
                    $('.payment_field').hide();
                }
            })

            $('#emp_select').on('change', function(){
              
                 $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url:  "{{ route('admin.users.salary.get-info') }}",
                    type: 'POST',
                    data : { id :$('#emp_select').val() },
                    success: function (data) {
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
                       
                       $('input[name="pay_type"][value="'+info.pay_type+'"]').prop('checked', true);
                        $('input[name="payment_method"][value="'+info.payment_method+'"]').prop('checked', true);
                       
                   
                    },
                    error: function (err) {
                        showErrorMessage(err);
                    }
                });
            })
        </script>
        @endpush
