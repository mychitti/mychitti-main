@extends('layouts.vendor.app')
@section('title','Attendance')
@push('css_or_js')
<script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
          <script>
                $(document).on('change',"#monthInp", function (){
               
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
                    <img src="{{asset('public/assets/admin/img/role.png')}}" class="w--26" alt="">
                </span>
                <span>
                   Attendance 
                    <span class="badge badge-soft-dark ml-2" id="itemCount">{{count($attendance)}}</span>
                </span>

            </h1>
            
        </div>
    </div>
    <!-- Page Heading -->

    <div class="card">
        <div class="card-header py-2 justify-content-end border-0">
            <div class="search--button-wrapper ">
                <form  class="search-form" action="">

                    <!-- Search -->
                    <div class="">
                       <input type="month" id="monthInp" name="month" class="form-control" min="2020-01" value="{{$currentmonth}}" />
                    </div>
                    <!-- End Search -->
                </form>
         
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
                        <th class="border-0">{{translate('messages.#')}}</th>
                        <th class="border-0">Date</th>
                        <th class="border-0">In Time</th>
                        <th class="border-0">Out Time</th>
                        <th class="border-0">Total duration</th>
                        <th class="border-0">Extra Duty @if(!empty($shiftName))<span class="text-muted" style="font-weight:400;">({{ $shiftName }})</span>@endif</th>
                    </tr>
                    </thead>
                    <tbody id="set-rows">
                    @foreach($attendance as $k => $e)
                    @php
                        $k = $k + 1;
                        $workedSecs = max(0, strtotime($e->out_time) - strtotime($e->in_time));
                        // Prefer the value snapshotted at clock-out; fall back to computed for older rows.
                        $extraSecs  = !is_null($e->overtime_seconds ?? null)
                            ? (int) $e->overtime_seconds
                            : max(0, $workedSecs - ($shiftSeconds ?? 32400));
                    @endphp
                        <tr>
                            <th scope="row">{{$k}}</th>
                            <td class="text-capitalize text-break">{{$e->date}}</td>
                            <td>{{explode(' ', $e->in_time)[1]}}{{ (explode(' ',$e->out_time)[0] != explode(' ',$e->in_time)[0]) ? ' ('. explode(' ',$e->in_time)[0] . ')' : '';}} </td>
                            <td>{{explode(' ',$e->out_time)[1] }}{{ (explode(' ',$e->out_time)[0] != explode(' ',$e->in_time)[0]) ? ' ('. explode(' ',$e->out_time)[0] . ')' : '';}}</td>
                            <td>{{getTotalTime($e->in_time, $e->out_time )}}</td>
                            <td>
                                @if($extraSecs >= 60)
                                    <span class="badge badge-soft-warning">+{{ floor($extraSecs / 3600) }}h {{ floor(($extraSecs % 3600) / 60) }}m</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        @php 
        function getTotalTime($startDateTime, $endDateTime) {
         $start = new DateTime($startDateTime);
        $end = new DateTime($endDateTime);
        $interval = $start->diff($end);
        $totalHours = $interval->days * 24 + $interval->h; // Total hours = (number of days * 24) + remaining hours
        $totalMinutes = ($interval->i > 0) ? $interval->i : 00; // Total minutes = remaining minutes if any
        $totalSeconds = ($interval->s > 0) ? $interval->s : 00; // Total seconds = remaining seconds if any
        
            return   $totalHours.  ':'  . $totalMinutes .  ':' . $totalSeconds;
        }
        
        @endphp
      
        @if(count($attendance) === 0)
        <div class="empty--data">
            <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
            <h5>
                {{translate('no_data_found')}}
            </h5>
        </div>
        @endif
    </div>
</div>
@endsection

