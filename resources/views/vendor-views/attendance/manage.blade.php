@extends('layouts.vendor.app')

@section('title', 'Attendance Manage')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .day-list-item {
            display: flex;
            justify-content: space-around;
            flex-direction: column;
            width: 12%;
            height: 77px;
            margin: 5px;
            background: #ffffff5e;
            box-shadow: 0px 0px 3px #cbc6c6;
            padding: 4px;
            border-radius: 5px;
            font-size: 14px;
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

        .cl_calendar {
            padding: 12px;
        }

        @media (max-width: 700px) {
            .main_crd {
                padding: 0 !important;
            }

            .resturant-card {
                padding: 9px;
                text-align: center;
            }

            .cl_calendar {
                padding: 5px;
            }

            .day-list-item {
                margin: 3px;
                width: 12%;
                height: 60px;
            }

            .att_status {
                font-size: 11px;
                appearance: none;
                -webkit-appearance: none;
                -moz-appearance: none;
                background-image: none;
            }
        }
    </style>
@endpush

@section('content')


    <!--echo $sundays_in_month;-->
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Attendance Manage </h1>
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
            @csrf
            <input type="hidden" id="staff_id" name="staff_id" value="{{ isset($staff->id) ? $staff->id : '' }}">
            <div class="col-md-12">
                <div class="card h-100 border-0 shadow-none">
                    <div class="att_count_outer">
                        <div class="row g-3 mb-3 att_count_inner">
                            <div class="col-6 col-xl-2 ">
                                <div class="resturant-card card--bg-1">
                                    <h4 class="title">
                                        {{ $days_in_month - $sundays_in_month - $day_data['absent'] - $day_data['cl'] - $day_data['holiday'] }}
                                    </h4>
                                    <span class="subtitle">Present</span>

                                </div>
                            </div>
                            <div class="col-6 col-xl-2">
                                <div class="resturant-card card--bg-2">
                                    <h4 class="title">{{ $day_data['absent'] }}</h4>
                                    <span class="subtitle">Absent</span>

                                </div>
                            </div>
                            <div class="col-6 col-xl-2">
                                <div class="resturant-card card--bg-3">
                                    <h4 class="title">{{ $day_data['halfday'] }}</h4>
                                    <span class="subtitle">Half Days</span>

                                </div>
                            </div>
                            <div class="col-6 col-xl-2">
                                <div class="resturant-card card--bg-4">
                                    <div class="d-flex align-items-end">
                                        <h4 class="title mb-0"> {{ $day_data['cl'] + $day_data['sl'] }} </h4> <span
                                            class="">(CL: {{ $day_data['cl'] }}, SL: {{ $day_data['sl'] }}) </span>
                                    </div>

                                    <span class="subtitle">Leaves</span>

                                </div>
                            </div>
                            <div class="col-6 col-xl-2">
                                <div class="resturant-card card--bg-1">
                                    <h4 class="title">{{ $day_data['holiday'] }}</h4>
                                    <span class="subtitle">Holiday</span>

                                </div>
                            </div>

                            <div class="col-6 col-xl-2">
                                <div class="resturant-card card--bg-2">
                                    <h4 class="title">{{ $sundays_in_month }}</h4>
                                    <span class="subtitle">Sundays</span>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!--<h4 class="m-3 mb-0">Personal Information</h4>-->
                    <div class="card-body main_crd">
                        <div class="row">
                            <div class="col-md-3 shadow shadow-sm m-2 py-3">
                                <form action="">
                                    <label>Year</label>
                                    <select name="year" style="width: 100%;" class="form-control">
                                        @for ($m = 2000; $m <= 2030; $m++)
                                            <option value="{{ $m }}"
                                                {{ $filter_year == $m ? 'selected' : '' }}>{{ $m }}</option>
                                        @endfor
                                    </select>
                                    <label>Month</label>
                                    <select name="month" style="width: 100%;" class="form-control">

                                        @for ($m = 1; $m <= 12; $m++)
                                            @php $month = date('F', mktime(0,0,0,$m, 1, date('Y')));@endphp
                                            <option value="{{ $m }}"
                                                {{ $filter_month == $m ? 'selected' : '' }}>{{ $month }}</option>
                                        @endfor

                                    </select>
                                    <button class="btn btn--primary btn-outline-primary w-100 my-3">View</button>
                                </form>
                            </div>
                            <div class="col-md-8 shadow shadow-sm m-2 calendar cl_calendar"
                                style="background-image: linear-gradient(45deg, #ffffff, #6f9ef557);
}">
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
                                        <li class="day-list-item">{{ $t }}
                                            @if (date('l', strtotime(date($filter_year . '-' . $filter_month . '-' . $t))) == 'Sunday' ||
                                                    (!empty($attendance) && in_array($t, $daArr) && $labelArr[array_search($t, $daArr)] == 'Sun'))
                                                <select class="att_status"
                                                    style="height: 25px;width: 100%;border: 0px;background: #ffffff80; pointer-events:none;"
                                                    data-id="{{ $t }}">
                                                    <option value="P" selected>Sun</option>
                                                </select>
                                            @else
                                                <select class="att_status"
                                                    style="height: 25px;width: 100%;border: 0px;background: #ffffff80;"
                                                    data-id="{{ $t }}">
                                                    <option value=""
                                                        {{ in_array($t, $daArr) && $labelArr[array_search($t, $daArr)] == '' ? 'selected' : '' }}>
                                                        -</option>
                                                    <option value="P"
                                                        {{ in_array($t, $daArr) && $labelArr[array_search($t, $daArr)] == 'P' ? 'selected' : '' }}>
                                                        P</option>
                                                    <option value="CL"
                                                        {{ in_array($t, $daArr) && $labelArr[array_search($t, $daArr)] == 'CL' ? 'selected' : '' }}>
                                                        CL</option>
                                                    <option value="SL"
                                                        {{ in_array($t, $daArr) && $labelArr[array_search($t, $daArr)] == 'SL' ? 'selected' : '' }}>
                                                        SL</option>
                                                    <option value="A"
                                                        {{ in_array($t, $daArr) && $labelArr[array_search($t, $daArr)] == 'A' ? 'selected' : '' }}>
                                                        A</option>
                                                    <option value="HDF"
                                                        {{ in_array($t, $daArr) && $labelArr[array_search($t, $daArr)] == 'HDF' ? 'selected' : '' }}>
                                                        HDF</option>
                                                    <option value="HDS"
                                                        {{ in_array($t, $daArr) && $labelArr[array_search($t, $daArr)] == 'HDS' ? 'selected' : '' }}>
                                                        HDS</option>
                                                    <option value="HL"
                                                        {{ in_array($t, $daArr) && $labelArr[array_search($t, $daArr)] == 'HL' ? 'selected' : '' }}>
                                                        HL</option>
                                                </select>
                                            @endif
                                        </li>
                                    @endfor

                                </ul>
                            </div>
                        </div>


                        <div class="form-row">
                            @if (hasPermission('attendance_manage', 'edit'))
                                <form action="{{ route('vendor.attendance.save') }}" id="att_form" method="post">
                                    @csrf
                                    <input type ="hidden" class="month_inp" value="{{ $filter_month }}">
                                    <input type ="hidden" class="year_inp" value="{{ $filter_year }}">
                                    <input type ="hidden" class="emp_id" value="{{ $staff->id }}">
                                    <div class="col my-2 d-flex justify-content-end pr-5">
                                        <button class="btn  btn--primary btn-outline-primary"
                                            style="height: 44px;109px;">Update</button>
                                    </div>

                                </form>
                            @endif
                        </div>

                    </div>
                </div>
            </div>


            <div class="card col-12">
                <div class="card-header py-2 justify-content-end border-0 flex-wrap">
                    <div class="search--button-wrapper ">
                        <form class="search-form" action="">
                            <!-- Search -->
                            <div class="">
                                <input type="month" id="monthInp" name="month" class="form-control" min="2020-01"
                                    value="{{ $currentmonth }}" />
                            </div>
                            <!-- End Search -->
                        </form>
                    </div>
                    <div class="mx-2">
                        <b>Total Hours Worked : </b>
                        <span>{{ $data['time_worked'] }}</span>
                    </div>
                    <div class="mx-2">
                        <b>Late Arrivals : </b>
                        <span>{{ $data['late_arrivals'] }}</span>
                    </div>
                    <div class="mx-2">
                        <b>Early Departures : </b>
                        <span>{{ $data['early_departures'] }}</span>
                    </div>
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
                                    <th class="border-0">Date</th>
                                    <th class="border-0">In Time</th>
                                    <th class="border-0">Out Time</th>
                                    <th class="border-0">Total duration</th>
                                </tr>
                            </thead>
                            <tbody id="set-rows">
                                @foreach ($attendanceLogs as $k => $e)
                                    @php $k = $k + 1 @endphp
                                    <tr>
                                        <th scope="row">{{ $k }}</th>
                                        <td class="text-capitalize text-break">{{ $e->date }}</td>
                                        <td>{{ explode(' ', $e->in_time)[1] }}{{ explode(' ', $e->out_time)[0] != explode(' ', $e->in_time)[0] ? ' (' . explode(' ', $e->in_time)[0] . ')' : '' }}
                                        </td>
                                        <td>{{ explode(' ', $e->out_time)[1] }}{{ explode(' ', $e->out_time)[0] != explode(' ', $e->in_time)[0] ? ' (' . explode(' ', $e->out_time)[0] . ')' : '' }}
                                        </td>
                                        <td>{{ getTotalTime($e->in_time, $e->out_time) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @php
                    function getTotalTime($startDateTime, $endDateTime)
                    {
                        $start = new DateTime($startDateTime);
                        $end = new DateTime($endDateTime);
                        $interval = $start->diff($end);
                        $totalHours = $interval->days * 24 + $interval->h; // Total hours = (number of days * 24) + remaining hours
                        $totalMinutes = $interval->i > 0 ? $interval->i : 00; // Total minutes = remaining minutes if any
                        $totalSeconds = $interval->s > 0 ? $interval->s : 00; // Total seconds = remaining seconds if any

                        return $totalHours . ':' . $totalMinutes . ':' . $totalSeconds;
                    }

                @endphp

                @if (count($attendanceLogs) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif
            </div>




        @endsection

        @push('script_2')
            <script>
                $("#att_form").on('submit', function(e) {
                    e.preventDefault();
                    var filteredElements = $(".att_status");
                    var daysArr = [];
                    var statusArr = [];
                    var month = $('.month_inp').val();
                    var year = $('.year_inp').val();
                    var emp_id = $('.emp_id').val();

                    filteredElements.each(function() {
                        if ($(this).val() != '') {
                            statusArr.push($(this).val());
                            daysArr.push($(this).attr('data-id'));
                        }
                    });

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.post({
                        url: '{{ route('vendor.attendance.save') }}',
                        data: {
                            statusArr: statusArr,
                            daysArr: daysArr,
                            year: year,
                            month: month,
                            emp_id: emp_id
                        },
                        success: function(data) {
                            var data = JSON.parse(data)
                            console.log(data.msg)
                            toastr.success(data.msg);
                            var url = window.location.href;
                            $(".att_count_outer").load(url + ' .att_count_inner')
                        },
                    });
                })
            </script>
        @endpush
