@extends('layouts.admin.app')

@section('title', 'Lead Config')

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

        <!-- Zone-wise Wallet Minimum Balance -->
        <div class="row mt-3">
            <div class="col-md-5"> 
                <div class="card h-100">
                    <div class="card-header flex-column align-items-start   ">
                        <h5 class="card-title mb-0">Add Zone Wallet Minimum Balance</h5>
                        <p class="text-muted small mb-0">Set the minimum wallet balance required per zone. Leads will not be distributed to vendors whose wallet balance is below this threshold.</p>
                    </div> 
                    <div class="card-body">
                        <form action="{{ route('admin.service.zone-wallet-config-save') }}" method="post">
                            @csrf
                            <div class="form-group">
                                <label>Zone <span class="text-danger">*</span></label>
                                <select name="zone_id" class="form-control js-select2-custom" required>
                                    <option value="">Select Zone</option>
                                    @foreach ($zones as $zone)
                                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </div>  
                            <div class="form-group">
                                <label>Category <small class="text-muted">(Optional - leave empty for zone level)</small></label>
                                <select name="category_id[]" class="form-control js-select2-custom" multiple>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select> 
                            </div>
                            <div class="form-group">
                                <label>Minimum Balance <span class="text-danger">*</span></label>
                                <input type="number" name="min_balance" class="form-control" placeholder="Ex: 100" min="0" step="0.01" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Zone Wallet Configs</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('sl') }}</th>
                                    <th>Zone</th> 
                                    <th>Category</th>
                                    <th>Min Balance</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($zoneWalletConfigs as $config)
                                    <tr> 
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $config->zone?->name ?? 'N/A' }}</td>
                                        <td>{{ $config->category?->name ?? 'All Categories' }}</td>
                                        <td>
                                            <form action="{{ route('admin.service.zone-wallet-config-update', $config->id) }}" method="post" class="d-flex align-items-center">
                                                @csrf
                                                <input type="number" name="min_balance" value="{{ $config->min_balance }}" class="form-control form-control-sm" style="width: 120px;" min="0" step="0.01" required>
                                                <button type="submit" class="btn btn-sm btn-primary ml-2">Update</button>
                                            </form>
                                        </td>
                                        <td>
                                            <a class="btn btn-sm btn--danger btn-outline-danger" href="{{ route('admin.service.zone-wallet-config-delete', $config->id) }}" title="Delete">
                                                <i class="tio-delete-outlined"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty  
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No zone configs added yet</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    @endsection

    @push('script_2')
    @endpush
