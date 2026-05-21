@extends('layouts.vendor.app')

@section('title',translate('Lead List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Leads<span class="badge badge-soft-dark ml-2" id="itemCount">{{count($leads)}}</span></h1>
            <div class="page-header-select-wrapper">

                {{-- <div class="select-item">
                    <select name="module_id" class="form-control js-select2-custom"
                            onchange="set_filter('{{url()->full()}}',this.value,'module_id')" title="{{translate('messages.select')}} {{translate('messages.modules')}}">
                        <option value="" {{!request('module_id') ? 'selected':''}}>{{translate('messages.all')}} {{translate('messages.modules')}}</option>
                        @foreach (\App\Models\Module::notParcel()->get() as $module)
                            <option
                                value="{{$module->id}}" {{request('module_id') == $module->id?'selected':''}}>
                                {{$module['module_name']}}
                            </option>
                        @endforeach
                    </select>
                </div> --}}
                @if(!isset(auth('vendor')->user()->zone_id))
                <div class="select-item">
                    <select name="zone_id" class="form-control js-select2-custom"
                            onchange="set_filter('{{url()->full()}}',this.value,'zone_id')">
                        <option value="" {{!request('zone_id')?'selected':''}}>{{ translate('messages.All_Zones') }}</option>
                        @foreach(\App\Models\Zone::orderBy('name')->get() as $z)
                            <option
                                value="{{$z['id']}}" {{isset($zone) && $zone->id == $z['id']?'selected':''}}>
                                {{$z['name']}}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
        </div>
        <!-- End Page Header -->


        <!-- Resturent Card Wrapper -->
        <div class="row g-3 mb-3">
               <div class="col-xl-2 col-sm-6">
                <div class="resturant-card card--bg-1">
                    <h4 class="title">{{count($leads)}}</h4>
                    <span class="subtitle">All Leads</span>
                 
                </div>
            </div>
            <div class="col-xl-2 col-sm-6">
                <div class="resturant-card card--bg-2">
                    <h4 class="title">{{count($new)}}</h4>
                    <span class="subtitle">New Leads</span>
                  
                </div>
            </div>
            <div class="col-xl-2 col-sm-6">
                <div class="resturant-card card--bg-3">
                    <h4 class="title">{{count($followups)}}</h4>
                    <span class="subtitle">Follow Ups</span>
                   
                </div>
            </div>
            <div class="col-xl-2 col-sm-6">
                <div class="resturant-card card--bg-4">
                    <h4 class="title">{{count($completed)}}</h4>
                    <span class="subtitle">Completed</span>
                   
                </div>
            </div>
            <div class="col-xl-2 col-sm-6">
                <div class="resturant-card card--bg-1">
                    <h4 class="title">{{count($onhold)}}</h4>
                    <span class="subtitle">On Hold</span>
                 
                </div>
            </div>
            
             <div class="col-xl-2 col-sm-6">
                <div class="resturant-card card--bg-2">
                    <h4 class="title">{{count($closed)}}</h4>
                    <span class="subtitle">Closed</span>
                 
                </div>
            </div>
        </div>
        <!-- Resturent Card Wrapper -->
        <!-- Transaction Information -->
            <form action="" >
        <ul class="transaction--information ">
               
            <li class="text--info">
                 <div class="form-group date_wise">
                    <label class="input-label" for="exampleFormControlSelect1">From Date<span
                            class="input-label-secondary"></span></label>
                    <input value="{{$from_date}}" type="date" name="from_date" class="form-control">
                </div>
            </li>
             <li class="text--info">
                 <div class="form-group date_wise">
                    <label class="input-label" for="exampleFormControlSelect1">To Date<span
                            class="input-label-secondary"></span></label>
                    <input value="{{$to_date}}" type="date" name="to_date" class="form-control">
                </div>
            </li>
            <style>
                .selection{
                    width: 100%;
                }
            </style>
             <li class="text--info">
                 <div class="form-group date_wise">
                    <label class="input-label" for="exampleFormControlSelect1">Status<span
                            class="input-label-secondary"></span></label>
                   <select name="status" class="form-control js-select2-custom"
                            >
                        <option value="all">All</option>
                          <option {{ $filter_status == 'New' ? 'selected': '';}} value="New">New</option>
                            <option {{ $filter_status == 'Completed' ? 'selected': '';}} value="Completed">Completed</option>  
                            <option {{ $filter_status == 'Follow Ups' ? 'selected': '';}} value="Follow Ups">Follow Ups</option>
                             <option {{ $filter_status == 'Hold' ? 'selected': '';}} value="Hold">Hold</option>
                              <option {{ $filter_status == 'Not Interested' ? 'selected': '';}} value="Not Interested">Not Interested</option>
                             <option {{ $filter_status == 'Closed' ? 'selected': '';}} value="Closed">Closed</option>
                    </select>
                </div>
            </li>
               <button type="submit" class="btn btn--primary btn-outline-primary">Filter</button>
            </li>
        </ul>
            </form>
        <!-- Transaction Information -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Leads List</h5>
                    <form action="javascript:" id="search-form" class="search-form">
                                    <!-- Search -->
                        @csrf
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" name="search" class="form-control"
                                    placeholder="Search Client" aria-label="{{translate('messages.search')}}" required>
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>

                        </div>
                        <!-- End Search -->
                    </form>
                 
                    <!-- End Unfold -->
                </div>
            </div>
            <!-- End Header -->

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
                        <th class="border-0">{{translate('sl')}}</th>
                        <th class="border-0">Client Info</th>
                        <th class="border-0">Service</th>
                        <th class="border-0">Channel</th>
                        <th class="text-uppercase border-0">Status</th>
                        <th class="text-uppercase border-0">Follow Up Date</th>
                        <th class="text-uppercase border-0">Approval</th>
                        <th class="text-center border-0">{{translate('messages.action')}}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                   @foreach($leads as $lead)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div>
                                    <a href="" class="table-rest-info" alt="view store">
                                    
                                        <div class="info"><div class="text--title">
                                            {{$lead->client_name}}
                                            </div>
                                                <div class="font-light">
                                                {{$lead->client_mobile}}
                                            </div>
                                            <div class="font-light">
                                                {{$lead->client_email}}
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <span class="d-block font-size-sm text-body">
{{$lead->service_name}}
                                </span>
                            </td>
                            <td>
                                <span class="d-block font-size-sm text-body">
                                 {{$lead->channel}}
                                </span>
                             
                            </td>
                            <td>
                            <form id="status-form{{$lead->id}}" method="post" action="{{route('vendor.lead.status-change')}}">
                                @csrf
                                <input type="hidden" name="lead_id" value="{{$lead->id}}">
                                 <select name="status" class="form-control js-select2-custom"
                                 onchange="document.getElementById('status-form{{$lead->id}}').submit()">
                                 <option value="New" {{ ($lead->status == 'New') ?'selected':''}}>New</option>
                                 <option value="Completed" {{ ($lead->status == 'Completed') ?'selected':''}} >Completed</option>  
                                 <option value="Follow Ups" {{ ($lead->status == 'Follow Ups') ?'selected':''}}>Follow Ups</option>
                                 <option value="Hold" {{ ($lead->status == 'Hold') ?'selected':''}}>Hold</option>
                                 <option value="Not Interested" {{ ($lead->status == 'Not Interested') ?'selected':''}}>Not Interested</option>
                                 <option value="Closed" {{ ($lead->status == 'Closed') ?'selected':''}}>Closed</option>
                             </select>
                             </form>
                            </td>
                            <td>
                           <span class="d-block font-size-sm text-body">
                                     {{$lead->follow_up_date == NULL ? '-' : $lead->follow_up_date; }}
                                </span>
                            </td>
                             <td>
                            @if($lead->approval == NULL)
                            - 
                            @elseif($lead->approval == 'accept')
                            <span class="text-success">Accepted</span>
                            @else
                            <span class="text-danger">Rejected</span>
                            @endif
                            </td>
                            <td>
                                <div class="btn--container justify-content-center">
                                    <a class="btn  btn--warning btn-outline-warning"
                                            href="{{ route('vendor.lead.manage', [$lead->id]) }}"
                                            title="{{ translate('messages.view') }}">Manage
                                        </a>
                              
                                    <a style="min-width:50px;" class="btn  btn--danger btn-outline-danger" href="{{ route('vendor.lead.delete', [$lead->id]) }}"
                                    title="{{translate('messages.delete')}} {{translate('messages.store')}}"><i class="tio-delete-outlined"></i>
                                    </a>
                                  
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @if(count($leads))
                <hr>
              @else
                <div class="page-area">
                </div>
                <div class="empty--data">
                    <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                    <h5>
                        {{translate('no_data_found')}}
                    </h5>
                </div>
@endif
            </div>
            <!-- End Table -->
        </div>
        <!-- End Card -->
    </div>

@endsection

@push('script_2')
    <script>
        function status_change_alert(url, message, e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: 'No',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href=url;
                }
            })
        }
        $(document).on('ready', function () {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            var datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

            $('#column1_search').on('keyup', function () {
                datatable
                    .columns(1)
                    .search(this.value)
                    .draw();
            });

            $('#column2_search').on('keyup', function () {
                datatable
                    .columns(2)
                    .search(this.value)
                    .draw();
            });

            $('#column3_search').on('keyup', function () {
                datatable
                    .columns(3)
                    .search(this.value)
                    .draw();
            });

            $('#column4_search').on('keyup', function () {
                datatable
                    .columns(4)
                    .search(this.value)
                    .draw();
            });


            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function () {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>

@endpush