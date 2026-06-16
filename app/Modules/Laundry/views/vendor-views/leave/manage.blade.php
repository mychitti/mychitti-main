@extends('layouts.vendor.app')

@section('title', 'Leave Manage')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /*background-color: #afcae9;*/
        .day-list-item {
            display: flex;
            flex-direction: column;
            width: 12%;
            height: 58px;
            margin: 5px;
            background: #ffffff5e;
            box-shadow: 0px 0px 3px #cbc6c6;
            padding: 4px;
            border-radius: 5px;
            font-size: 14px;
        }

        @media (max-width: 770px) {
            .day-list-item {
                height: 37px;

            }
        }

        .invisible-item {
            background: #ffffff00;
            box-shadow: 0px 0px 3px #cbc6c600;
        }

        .day-list {
            /*border: 1px solid white;*/
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            padding: 0px;
        }

        .day-name-list {
            display: flex;
            list-style: none;
            padding: 0px;
            margin-bottom: 0px;
        }

        .day-name-list-item {
            /* border: 1px solid white; */
            width: 12%;
            margin: 5px;
            font-weight: bold;
        }

        .selected-day {
            background-color: #fee4a0;
        }
    </style>
@endpush

@section('content')


    <!--echo $sundays_in_month;-->
    <div class="content container-fluid">
        @include('vendor-views.partials._hr_header', ['heroSubtitle' => 'Manage Leave · ' . (isset($staff) ? trim($staff->f_name . ' ' . $staff->l_name) : '')])



        @if (session()->has('msg'))
            <div class="alert alert-success" role="alert">
                {{ session('msg') }}
            </div>
        @endif
        <div class="row g-2">
            @csrf
            <input type="hidden" id="staff_id" name="staff_id" value="{{ isset($staff->id) ? $staff->id : '' }}">
            <div class="col-md-12 p-0">
                <div class="card h-100">

                    <!--<h4 class="m-3 mb-0">Personal Information</h4>-->
                    <div class="card-body row g-0 d-flex align-items-center p-0">
                        @if (hasPermission('leave_manage', 'view'))
                            <div class=" d-flex flex-column col-md-6 p-0">
                                <div class=" shadow shadow-sm m-2 py-3">
                                    <form action="" class="d-flex align-items-end row">
                                        <div class="col-md-4">


                                            <label>Year</label>
                                            <select name="year" class="form-control">
                                                @for ($m = 2000; $m <= 2030; $m++)
                                                    <option value="{{ $m }}"
                                                        {{ $filter_year == $m ? 'selected' : '' }}>{{ $m }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Month</label>
                                            <select name="month" class="form-control">

                                                @for ($m = 1; $m <= 12; $m++)
                                                    @php $month=date('F', mktime(0,0,0,$m, 1, date('Y')));@endphp <option value="{{ $m }}"
                                                        {{ $filter_month == $m ? 'selected' : '' }}>{{ $month }}
                                                    </option>
                                                @endfor

                                            </select>
                                        </div>
                                        <div class="col-md-4 mt-1">
                                            <button class=" btn btn--primary btn-outline-primary">View</button>
                                        </div>
                                    </form>
                                </div>
                                <div class=" shadow shadow-sm m-2 calendar p-3"
                                    style="background-image: linear-gradient(45deg, #ffffff, #fee4a08c);">
                                    <h1>{{ date('F', mktime(0, 0, 0, $filter_month, 10)) . ' ' . $filter_year }}</h1>
                                    <!--<p>Holidays and Daily Observances in the United States</a>-->
                                    <ul class="day-name-list">
                                        <li class="day-name-list-item">Mon</li>
                                        <li class="day-name-list-item">Tue</li>
                                        <li class="day-name-list-item">Wed</li>
                                        <li class="day-name-list-item">Thu</li>
                                        <li class="day-name-list-item">Fri</li>
                                        <li class="day-name-list-item">Sat</li>
                                        <li class="day-name-list-item">Sun</li>
                                    </ul>
                                    <ul class="day-list">

                                        @for ($d = 1; $d < $firstDayOfMonth; $d++)
                                            <li class="day-list-item invisible-item">
                                            </li>
                                        @endfor

                                        @for ($t = 1; $t <= $days_in_month; $t++)
                                            <li class="day-list-item" style="cursor:pointer;">{{ $t }}
                                                <!--<select class="att_status" style="height: 25px;width: 100%;border: 0px;background: #ffffff80;" data-id="{{ $t }}">-->
                                                <!--    <option value="P" {{ in_array($t, $daArr) && $labelArr[array_search($t, $daArr)] == 'P' ? 'selected' : '' }}>P</option>-->
                                                <!--    <option value="CL" {{ in_array($t, $daArr) && $labelArr[array_search($t, $daArr)] == 'CL' ? 'selected' : '' }}>CL</option>-->
                                                <!--    <option value="A" {{ in_array($t, $daArr) && $labelArr[array_search($t, $daArr)] == 'A' ? 'selected' : '' }}>A</option>-->
                                                <!--    <option value="HD" {{ in_array($t, $daArr) && $labelArr[array_search($t, $daArr)] == 'HD' ? 'selected' : '' }}>HD</option>-->
                                                <!--    <option value="HL" {{ in_array($t, $daArr) && $labelArr[array_search($t, $daArr)] == 'HL' ? 'selected' : '' }}>HL</option>-->
                                                <!--    <option value="Sun" {{ empty($attendance) && date('l', strtotime(date($filter_year . '-' . $filter_month . '-' . $t))) == 'Sunday' ? ' selected' : '' }} {{ !empty($attendance) && in_array($t, $daArr) && $labelArr[array_search($t, $daArr)] == 'Sun' ? 'selected' : '' }}>Sun</option>-->
                                                <!--</select>-->
                                            </li>
                                        @endfor

                                    </ul>
                                </div>
                            </div>
                        @endif
                        <div class=" d-flex flex-column p-3 col-md-6 p-0">
                            @if (hasPermission('leave_manage', 'view'))
                                <div class="border rounded my-2 p-2">
                                    <h4>Leave Balance of
                                        {{ date('F', mktime(0, 0, 0, $filter_month, 10)) . ' ' . $filter_year }}</h4>
                                    <table class="table">
                                        <tr>
                                            <td>Casual Leaves</td>
                                            <td>{{ $monthlyClleaveBalance }}</td>
                                        </tr>
                                        <tr>
                                            <td>Sick Leaves</td>
                                            <td>{{ $monthlySlleaveBalance }}</td>
                                        </tr>
                                    </table>
                                </div>
                            @endif
                            @if (hasPermission('leave_manage', 'add'))
                                <h4>Add a Leave</h4>
                                <label>Leave Type</label>
                                <select name="month" class="form-control leave_type">
                                    <option value="" selected disabled>-- select --</option>
                                    <option value="CL">Casual Leave</option>
                                    <option value="SL">Sick Leave</option>
                                    <option value="HCL">Half Day Casual Leave</option>
                                    <option value="HSL">Half Day Sick Leave</option>
                                    <option value="HDF">Half Day LOP (first half)</option>
                                    <option value="HDS">Half Day LOP (second half)</option>
                                    <option value="HL">Holiday</option>
                                </select>
                                <label>Reason</label>
                                <textarea class="form-control reason_inp" placeholder="Reason" row='4'></textarea>

                                <input type="hidden" class="month_inp" value="{{ $filter_month }}">
                                <input type="hidden" class="year_inp" value="{{ $filter_year }}">
                                <input type="hidden" class="emp_id" value="{{ $staff->id }}">
                                <button class="btn  btn--primary btn-outline-primary my-2" type="button" id="addBtn"
                                    style="height: 44px;109px;">Add Leave</button>
                            @endif
                        </div>


                    </div>
                    @if (hasPermission('leave_manage', 'view'))
                        <div class="card-body">
                            <h1>Pending Leaves</h1>
                            <div class="table-responsive datatable-custom">
                                <table id="columnSearchDatatable"
                                    class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                                    data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":true

                        }'>
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="border-0">{{ translate('sl') }}</th>
                                            <th class="border-0">Leave Type</th>
                                            <th class="border-0">Leave Date</th>
                                            <th class="border-0">Reason</th>
                                            <th class="border-0">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody id="set-rows">
                                        @foreach ($pendingleaves as $lead)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <div>
                                                        <a href="#" class="table-rest-info" alt="view store">

                                                            <div class="info">
                                                                <div class="text--title">
                                                                    @if ($lead['leave_type'] == 'CL')
                                                                        {{ 'Casual Leave' }}
                                                                    @elseif($lead['leave_type'] == 'SL')
                                                                        {{ 'Sick Leave' }}
                                                                    @elseif($lead['leave_type'] == 'HCL')
                                                                        {{ 'Half Day Casual Leave' }}
                                                                    @elseif($lead['leave_type'] == 'HSL')
                                                                        {{ 'Half Day Sick Leave' }}
                                                                    @elseif($lead['leave_type'] == 'HDF')
                                                                        {{ 'Half Day LOP (first half)' }}
                                                                    @elseif($lead['leave_type'] == 'HDS')
                                                                        {{ 'Half Day LOP (second half)' }}
                                                                    @elseif($lead['leave_type'] == 'HL')
                                                                        {{ 'Holiday' }}
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </a>
                                                    </div>
                                                </td>
                                                <td>{{ $lead['leave_date'] }}</td>
                                                <td> {{ $lead['reason'] }}</td>
                                                <td>
                                                    @if (hasPermission('leave_manage', 'status_change'))
                                                        <a href="{{ route('vendor.approve-leave', ['id' => $lead['id']]) }}"
                                                            class="btn btn--primary">Approve</a>
                                                        <a href="{{ route('vendor.reject-leave', ['id' => $lead['id']]) }}"
                                                            class="btn btn--danger ">Reject</a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                            </div>

                        </div>
                        <div class="card-body">
                            <h1>Leave History of {{ date('F', mktime(0, 0, 0, $filter_month, 10)) . ' ' . $filter_year }}
                            </h1>
                            <div class="table-responsive datatable-custom">
                                <table id="columnSearchDatatable"
                                    class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                                    data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":true

                        }'>
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="border-0">{{ translate('sl') }}</th>
                                            <th class="border-0">Leave Type</th>
                                            <th class="border-0">Leave Date</th>
                                            <th class="border-0">Reason</th>
                                            <th class="border-0">Status</th>
                                        </tr>
                                    </thead>

                                    <tbody id="set-rows">
                                        @foreach ($leaves as $lead)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <div>
                                                        <a href="#" class="table-rest-info" alt="view store">

                                                            <div class="info">
                                                                <div class="text--title">
                                                                    @if ($lead['leave_type'] == 'CL')
                                                                        {{ 'Casual Leave' }}
                                                                    @elseif($lead['leave_type'] == 'SL')
                                                                        {{ 'Sick Leave' }}
                                                                    @elseif($lead['leave_type'] == 'HCL')
                                                                        {{ 'Half Day Casual Leave' }}
                                                                    @elseif($lead['leave_type'] == 'HSL')
                                                                        {{ 'Half Day Sick Leave' }}
                                                                    @elseif($lead['leave_type'] == 'HDF')
                                                                        {{ 'Half Day LOP (first half)' }}
                                                                    @elseif($lead['leave_type'] == 'HDS')
                                                                        {{ 'Half Day LOP (second half)' }}
                                                                    @elseif($lead['leave_type'] == 'HL')
                                                                        {{ 'Holiday' }}
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </a>
                                                    </div>
                                                </td>
                                                <td>{{ $lead['leave_date'] }}</td>
                                                <td> {{ $lead['reason'] }}</td>
                                                <td> {{ ucfirst($lead['status']) }}</td>

                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if (count($leaves))
                                    <hr>
                                @else
                                    <div class="page-area">
                                    </div>
                                    <div class="empty--data">
                                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                            alt="public">
                                        <h5>
                                            {{ translate('no_data_found') }}
                                        </h5>
                                    </div>
                                @endif
                            </div>

                        </div>
                    @endif
                </div>
            </div>




        @endsection

        @push('script_2')
            <script>
                $('.day-list-item').on('click', function() {
                    $('.day-list-item').removeClass('selected-day')
                    $(this).addClass('selected-day')

                })

                $('#addBtn').on('click', function() {
                    var slectedDay = $("li.selected-day").length;
                    var reaon = $('.reason_inp').val();
                    var leaveType = $('.leave_type').val();

                    if (slectedDay == 0) {
                        alert('Please select a day in calendar');

                    } else if (reaon == '') {
                        alert('Please Enter Reason');
                    } else if (leaveType == '') {
                        alert('Please select leave type');
                    } else {
                        var day = $("li.selected-day").text();
                        var month = $('.month_inp').val();
                        var year = $('.year_inp').val();
                        var emp_id = $('.emp_id').val();


                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });

                        $.post({
                            url: '{{ route('vendor.leave.save') }}',
                            data: {
                                day: day,
                                month: month,
                                year: year,
                                emp_id: emp_id,
                                leaveType: leaveType,
                                reason: reaon
                            },
                            success: function(res) { 
                                var data = typeof res === 'object' ? res : JSON.parse(res);
                                if (data.status) {
                                    window.location.reload();
                                } else {
                                    if (typeof toastr !== 'undefined') {
                                        toastr.error(data.msg || data.message || 'Something went wrong');
                                    } else {
                                        alert(data.msg || data.message || 'Something went wrong');
                                    }
                                }
                            },
                        });
                    }
                })
            </script>
        @endpush
