@extends('layouts.admin.app')

@section('title', 'Lead List')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Leads Configuration</h1>
            <div class="page-header-select-wrapper">


            </div>
        </div>
        <!-- End Page Header -->


        <div class="row">
            <!-- Card -->
            <div class="card col-12">
                <!-- Header -->
                <div class="py-2">
                    @php($leads_distribut_vendor = \App\CentralLogics\Helpers::get_business_settings('leads_distribut_vendor'))
                    @php($exp_count = \App\CentralLogics\Helpers::get_business_settings('exp_count'))
                    @php($exp_unit = \App\CentralLogics\Helpers::get_business_settings('exp_unit'))

                    <form class="row" action="{{ route('admin.service.config.update') }}" method="post">
                        @csrf
                        <div class="col-6">
                            <label for="exampleInputEmail1">Vendor Limit (Leads Distribution)</label>
                            <input type="number" value="{{ $leads_distribut_vendor ?? '' }}" name="leads_distribut_vendor"
                                placeholder="Vendor Limit" class="form-control">
                        </div>
                        <div class="col-6">
                            <label for="exampleInputEmail1">Expire Lead In </label>
                            <div class="row">
                                <input value="{{ $exp_count ?? '' }}" type="number" name="exp_count" placeholder="Ex: 15"
                                    class="form-control col-6">
                                <select name="exp_unit" class="form-control col-6">
                                    <option {{ isset($exp_unit) && $exp_unit == 'minutes' ? 'selected' : '' }}
                                        value="minutes" selected>Minutes</option>
                                    <option {{ isset($exp_unit) && $exp_unit == 'hours' ? 'selected' : '' }} value="hours">
                                        Hours</option>
                                    <select>
                            </div>
                        </div>
                        <button class="btn btn-primary m-3">Save Changes</button>
                    </form>
                </div>
                <!-- End Header -->

            </div>
            <div class="card col-6 mt-3">
                <div class="">
                    <div class="search--button-wrapper">
                        <h5 class="card-title">Lead Common Issues</h5>
                    </div>
                </div>
                <form class="w-100" action="{{ route('admin.service.common-issue.save') }}" method="post">
                    @csrf
                    <input type="hidden" id="lead_id" name="lead_id" value="">
                    <div class="row">
                        <div class="ml-1 col-9 p-0 form-row d-flex ">
                            <input type="text" name="issue" placeholder="Issue" class="form-control">
                        </div>


                        <div class="p-0 col-2">
                            <button class="btn btn--primary ">Save</button>
                        </div>

                    </div>
                </form>

                <!-- Table -->
                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable"
                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                        data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false

                        }'>
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('sl') }}</th>
                                <th class="border-0">Issue</th>
                                <th class="border-0">Action</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                            @foreach ($reported_issue_list as $issue)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div>
                                                <div class="info">
                                                    <div class="text--title">
                                                        {{ $issue->issue }}
                                                    </div>

                                                </div>
                                          
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <a style="min-width:50px;" class="btn  btn--danger btn-outline-danger"
                                                href="{{ route('admin.service.common-issue.delete', [$issue->id]) }}"
                                                title="{{ translate('messages.delete') }}"><i
                                                    class="tio-delete-outlined"></i>
                                            </a>

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if (count($reported_issue_list))
                        <hr>
                    @else
                        <div class="page-area">
                        </div>
                        <div class="empty--data">
                            <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                            <h5>
                                {{ translate('no_data_found') }}
                            </h5>
                        </div>
                    @endif
                </div>
            </div>
            <!-- End Card -->
        </div>

    @endsection

    @push('script_2')
    @endpush
