@extends('layouts.vendor.app')

@section('title', translate('messages.POS Report'))

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush


@section('content')
    <div class="content container-fluid">


        @if (hasPermission('pos', 'report'))

            <div class="card mt-3">
                <div class="card-header py-2 border-0">
                    <div class="search--button-wrapper">
                        <h5 class="card-title">{{ translate('messages.POS_report') }}<span class="badge badge-soft-dark ml-2"
                                id="itemCount">{{ count($items) }}</span></h5>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn--primary mb-0" data-toggle="modal"
                                data-target="#calendarModal">
                                Calendar
                            </button>
                            <form action="" class="h-100">
                                <!-- Search -->
                                <div class="input-group input--group" style="    flex-wrap: nowrap !important; ">
                                    <input type="search" style="min-width:210px;height: 100%;     padding: 11px 10px;"
                                        name="search" value="{{ request()?->search ?? null }}" class="form-control "
                                        placeholder="{{ translate('messages.search by item name') }}">
                                    <button type="submit" class="btn btn--secondary "><i class="tio-search"></i></button>
                                </div>
                                <!-- End Search -->
                            </form>
                            <a href="{{ route('vendor.pos.report', ['action' => 'export']) }}"
                                class="btn btn-outline-primary">{{ translate('messages.export') }}</a>
                            <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning"
                                type="button" data-toggle="modal"
                                data-target="#dateRangeModal">{{ translate($preset) }}</button>

                            <form action="" class="date-range-form ">
                                @include('vendor-views/form_modals/date_range')

                                <select class="form-control mx-1 js-select2-custom" name="branch"
                                    onchange="this.form.submit()">
                                    <option {{ request()->branch == 'all' ? 'selected' : '' }} value="all">All Branches
                                    </option>
                                    @foreach ($branches as $key => $branch)
                                        <option {{ $branch_id == $branch->id ? 'selected' : '' }}
                                            value="{{ $branch->id }}">{{ ucfirst($branch->name) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>

                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive datatable-custom">
                        <table id="columnSearchDatatable"
                            class="table table-borderless table-thead-bordered table-align-middle"
                            data-hs-datatables-options='{
                            "isResponsive": false,
                            "isShowPaging": false,
                            "paging":false,
                        }'>
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">{{ translate('sl') }}</th>
                                    <th class="border-0 ">Name</th>
                                    <th class="border-0 ">Branch</th>
                                    <th class="border-0 ">Sold</th>
                                    <th class="border-0 ">Sales Amount</th>
                                    <th class="border-0 ">Cancelled</th>
                                    <th class="border-0 ">Cancelled Amount</th>
                                    <th class="border-0 ">Remaining Stock</th>
                                </tr>
                            </thead>

                            <tbody id="table-div">
                                @foreach ($items as $key => $item)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>
                                            {{ ucfirst($item->item_name) }}
                                        </td>
                                        <td>
                                            {{ ucfirst($item->branch_name) }}
                                            @if ($item->branch_type == 'main')
                                                <span class=" text-success mb-0">(Main)</span>
                                            @else
                                                <span class=" text-warning mb-0">(Sub)</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $item->total_qty }}
                                        </td>

                                        <td>
                                            {{ _price($item->total_sales) }}
                                        </td>
                                        <td>
                                            <span class="text-danger">{{ $item->cancelled_qty }}</span>
                                        </td>

                                        <td>
                                            <span class="text-danger">{{ _price($item->cancelled_sales) }}</span>
                                        </td>
                                        <td>{{ _getRemainingStock($item->item_id, $item->branch_id) }}</td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if (count($items) !== 0)
                    <hr>
                @endif
                <div class="page-area">
                </div>
                @if (count($items) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif
            </div>
        @endif
    </div>
    @include('vendor-views.form_modals.pos_calendar')
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
    @include('vendor-views.salespoint.calendar-js')
@endpush
