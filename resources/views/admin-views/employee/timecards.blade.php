@extends('layouts.admin.app')
@section('title', 'Timecards - ' . $emp->f_name . ' ' . $emp->l_name)
@push('css_or_js')
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
                <span>
                    Timecards - {{ $emp->f_name . ' ' . $emp->l_name }}
                    <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count($timecards) }}</span>
                </span>
            </h1>
            <a href="{{ route('admin.employee.view', [$emp->id]) }}" class="btn btn-outline-primary">
                <i class="tio-arrow-backward"></i> Back to Profile
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-2 justify-content-end border-0">
            <div class="search--button-wrapper">
                <form class="search-form" action="">
                    <div>
                        <input type="month" id="monthInp" name="month" class="form-control" min="2020-01" value="{{ $currentmonth }}" />
                    </div>
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
                            <th class="border-0">{{ translate('messages.#') }}</th>
                            <th class="border-0">Date</th>
                            <th class="border-0">In Time</th>
                            <th class="border-0">Out Time</th>
                            <th class="border-0">Total Duration</th>
                        </tr>
                    </thead>
                    <tbody id="set-rows">
                        @foreach($timecards as $k => $e)
                            <tr>
                                <th scope="row">{{ $k + 1 }}</th>
                                <td class="text-capitalize text-break">{{ $e->date }}</td>
                                <td>{{ explode(' ', $e->in_time)[1] }}{{ (explode(' ', $e->out_time)[0] != explode(' ', $e->in_time)[0]) ? ' (' . explode(' ', $e->in_time)[0] . ')' : '' }}</td>
                                <td>{{ explode(' ', $e->out_time)[1] }}{{ (explode(' ', $e->out_time)[0] != explode(' ', $e->in_time)[0]) ? ' (' . explode(' ', $e->out_time)[0] . ')' : '' }}</td>
                                <td>
                                    @php
                                        $start = new DateTime($e->in_time);
                                        $end = new DateTime($e->out_time);
                                        $interval = $start->diff($end);
                                        $totalHours = $interval->days * 24 + $interval->h;
                                        $totalMinutes = $interval->i ?: 0;
                                        $totalSeconds = $interval->s ?: 0;
                                    @endphp
                                    {{ $totalHours . ':' . str_pad($totalMinutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad($totalSeconds, 2, '0', STR_PAD_LEFT) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if(count($timecards) === 0)
        <div class="empty--data">
            <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
            <h5>{{ translate('no_data_found') }}</h5>
        </div>
        @endif
    </div>
</div>
@endsection
