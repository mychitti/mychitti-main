@extends('layouts.vendor.app')
@section('title', 'My Salary')
@push('css_or_js')
    <style>
      

        .nav-link.active {
            color: #ffffff !important;
            background-color: #24bac3 !important;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
        crossorigin="anonymous"></script>
    <script>
        $(document).on('change', "#monthInp", function() {

            $('.search-form').submit()
        })
    </script>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h1 class="page-header-title mb-2">
                    <span class="page-header-icon">
                        <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                    </span>
                    <span> My Salary
                        <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count($all_salaries) }}</span>
                    </span>
                </h1>
                <button class="btn btn--primary" data-toggle="modal" data-target="#advanceSalaryModal">Request Advance
                    Payment</button>
            </div>
        </div>
        <div>
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a href="{{route('vendor.salary-history')}}"  class="nav-link active"  
                       >Salary History</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a  href="{{route('vendor.advance-payment')}}" class="nav-link" 
                       >Advance History</a>
                </li>
            </ul>
        </div>
        <!-- Page Heading -->

        <div class="card">
            <div class="card-header py-2 justify-content-end border-0">

            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="datatable"
                        class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                        data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false
                        }'>
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('messages.#') }}</th>
                                <th class="border-0">Salary Month</th>
                                <th class="border-0">Payment Status</th>
                                <th class="border-0">Total Payable</th>
                                <th class="border-0">Base Salary</th>
                                <th class="border-0">Payable Salary</th>
                                <th class="border-0">Allowance</th>
                                <th class="border-0">deductions</th>
                                <th class="border-0">Bonus Incentives</th>
                                <th class="border-0">Leaves</th>
                            </tr>
                        </thead>
                        <tbody id="set-rows">
                            @foreach ($all_salaries as $k => $e)
                                @php $k = $k + 1 @endphp
                                <tr>
                                    <th scope="row">{{ $k }}</th>
                                    <td>{{ _monthNYear($e->salary_month) }} </td>
                                    <td>
                                        @if ($e->pay_status == 'paid')
                                            <span class="badge badge-soft-success">Paid
                                            </span>
                                        @else
                                            <span class="badge badge-soft-danger">Unpaid
                                            </span>
                                        @endif
                                        @if ($e->pay_reciept)
                                            <br>
                                            <a target="_blank"
                                                href="{{ asset('storage/app/public/vendor/documents/') . '/' . $e->pay_reciept }}">View
                                                Documemt</a>
                                        @endif
                                    </td>
                                    <td>{{ _price($e->total_payable) }} </td>
                                    <td>{{ _price($e->base_salary) }}</td>
                                    <td>{{ _price($e->payable_salary) }}</td>
                                    <td>{{ _price($e->allowance_amount) }}</td>
                                    <td>{{ _price($e->deductions) }}</td>
                                    <td>{{ _price($e->bonus_incentives) }}</td>
                                    <td>{{ $e->vacation_or_leave }} </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>


            @if (count($all_salaries) === 0)
                <div class="empty--data">
                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                    <h5>
                        {{ translate('no_data_found') }}
                    </h5>
                </div>
            @endif
        </div>
    </div>
    <div class="modal fade" id="advanceSalaryModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Request Advance Payment</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method='post' class="row" action="{{ route('vendor.salary.advance-request.store') }}">
                        @csrf
                        <div class="form-group  col-md-6">
                            <label for="exampleInputEmail1">Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp"
                                name="amount" placeholder="">
                            <small id="emailHelp" class="form-text text-muted">Must not be more than
                                salary</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="exampleCheck1">Required On <span class="text-danger">*</span></label>
                            <input type="date" name="required_on" min="{{ date('Y-m-d') }}" class="form-control"
                                id="exampleCheck1">
                        </div>
                        <div class="form-group col-md-12">
                            <label for="exampleInputPassword1">Reason (Optional)</label>
                            <textarea name="reason" class="form-control" id="exampleFormControlTextarea1"></textarea>
                        </div>

                        <div class="d-flex justify-content-end w-100">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
