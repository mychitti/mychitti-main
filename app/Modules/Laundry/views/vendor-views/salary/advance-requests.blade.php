@extends('layouts.vendor.app')
@section('title', 'Advance History')
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
                    <span>Advance History
                        <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count($advance_requests) }}</span>
                    </span>
                </h1>

                <button class="btn btn--primary" data-toggle="modal"
                    data-target="#advanceSalaryModal">{{ auth('vendor')->check() ? 'Create' : 'Request' }} Advance
                    Payment</button>
            </div>
        </div>
        @if (auth('vendor_employee')->check())
            <div>
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a href="{{ route('vendor.salary-history') }}" class="nav-link ">Salary History</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="{{ route('vendor.advance-payment') }}" class="nav-link active">Advance History</a>
                    </li>
                </ul>
            </div>
        @endif
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
                                @if(auth('vendor')->check())
                                <th class="border-0">Staff</th>

                                @endif
                                <th class="border-0">Amount</th>
                                <th class="border-0">Reason</th>
                                <th class="border-0">Required On</th>
                                <th class="border-0">Approved Amount</th>
                                <th class="border-0">Status</th>
                                <th class="border-0">Action</th>
                            </tr>
                        </thead>
                        <tbody id="set-rows">
                            @foreach ($advance_requests as $k => $e)
                                @php $k = $k + 1 @endphp
                                <tr>
                                    <th scope="row">{{ $k }}</th>
                                @if(auth('vendor')->check())

                                    <th scope="row">{!! $e->employee?->f_name .' ' . $e->employee?->l_name . ' <br> Id: '. $e->employee?->id   !!}</th>
                                    @endif
                                    <td>{{ _price($e->requested_amount) }}</td>
                                    <td>{{ Str::limit($e['reason'], 100, '...') }}</td>
                                    <td>{{ $e->required_on }}</td>
                                    <td>{{ $e->approved_amount ? _price($e->approved_amount) : '' }}</td>
                                    <td>
                                        @if ($e->status == 'approved')
                                            <span class="badge badge-soft-success">Approved
                                            </span>
                                        @elseif($e->status == 'pending')
                                            <span class="badge badge-soft-warning">Pending
                                            </span>
                                        @else
                                            <span class="badge badge-soft-danger">Rejected
                                            </span>
                                        @endif
                                    </td>
                                    @if (auth('vendor')->check())
                                    <td>
                                    
                                        <div class="btn--container justify-content-center">
                                        @if($e->status == 'pending')
                                            <a class="btn action-btn btn--primary btn-outline-primary form-alert"
                                                href="javascript:" data-id="category2-{{ $e['id'] }}"
                                                data-message="{{ translate('Want to approve this request') }}"
                                                title="{{ translate('messages.approve_request') }}"><i
                                                    class="tio-checkmark-circle"></i>
                                            </a>
                                            <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                href="javascript:" data-id="category-{{ $e['id'] }}"
                                                data-message="{{ translate('Want to reject this request') }}"
                                                title="{{ translate('messages.reject_request') }}"><i
                                                    class="tio-remove-circle-outlined"></i>
                                            </a>
                                            <form
                                                action="{{ route('vendor.salary.reject-advance', [$e['id']]) }}"
                                                method="get" id="category-{{ $e['id'] }}">
                                                @csrf @method('get')
                                            </form>
                                            <form
                                                action="{{ route('vendor.salary.approve-advance', [$e['id']]) }}"
                                                method="get" id="category2-{{ $e['id'] }}">
                                                @csrf @method('get')
                                            </form>
                                            @endif
                                        </div>
                                    @else
                                        <td></td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>


            @if (count($advance_requests) === 0)
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
                    <h5 class="modal-title" id="exampleModalLabel">{{ auth('vendor')->check() ? 'Create' : 'Request' }} Advance Payment</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method='post' class="row" action="{{ route('vendor.salary.advance-request.store') }}">
                        @csrf
                        @if (auth('vendor')->check())
                            <div class="form-group  col-md-6">
                                <label for="exampleInputEmail1">Staff <span class="text-danger">*</span></label>
                                <select name="emp_id" id="" class="form-control js-select2-custom">
                                    <option value=""></option>
                                    @foreach ($staff as $key => $value)
                                        <option value="{{ $value->id }}">{{ $value->f_name . ' ' . $value->l_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
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
