@extends('layouts.admin.app')

@section('title',translate('Quotation List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Quotation<span class="badge badge-soft-dark ml-2" id="itemCount">{{count($quotes)}}</span></h1>
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
        <!-- Transaction Information -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Quotation List</h5>
                    <form action="javascript:" id="search-form" class="search-form">
                                    <!-- Search -->
                        @csrf
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" name="search" class="form-control"
                                    placeholder="Search Quotation" aria-label="{{translate('messages.search')}}" required>
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
                        <th class="border-0">Subject</th>
                        <th class="border-0">Client Info</th>
                        <th class="border-0">Service</th>
                        <th class="border-0">Total Amount</th>
                        <th class="text-uppercase border-0">Status</th>
                        <th class="text-uppercase border-0">Quotation Date</th>
                        <th class="text-center border-0">{{translate('messages.action')}}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                   @foreach($quotes as $quote)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                                <td>
                                <span class="d-block font-size-sm text-body">
                                  {{$quote->subject}}
                                 </span>
                            </td>
                            <td>
                                <div>
                                    <a href="" class="table-rest-info" alt="view store">
                                    
                                        <div class="info"><div class="text--title">
                                            {{$quote->client_name}}
                                            </div>
                                                <div class="font-light">
                                                {{$quote->client_mobile}}
                                            </div>
                                            <div class="font-light">
                                                {{$quote->client_email}}
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </td>
                            <td>
                                    <!--echo implode(' __ ' , $ser_arr);-->
                                    <!--print_r($ser_arr);-->
                                <span class="d-block font-size-sm text-body">
                                    @php 
                                    $ser_arr = json_decode($quote->services)->service;
                                    $amount = array_sum(json_decode($quote->services)->amount);
                                    foreach($ser_arr as $ser){
                                        $item = _getWhere('items' , ['id' => $ser]);
                                        if($item && isset($item[0])){ 
                                           echo $item[0]->name . ', <br>';
                                        }else{
                                            echo '<span class="text-danger">Deleted</span>';
                                        }
                                    }
                                    @endphp
                                    <!--print_r(array_column($arr, 'service'));-->
                                 </span>
                            </td>
                                <td>
                                <span class="d-block font-size-sm text-body">
                                {{\App\CentralLogics\Helpers::format_currency($amount)}}
                                 </span>
                            </td>
                        
                            <td>
                            <form id="status-form{{$quote->id}}" method="post" action="{{route('admin.quotation.status-change')}}">
                                @csrf
                                <input type="hidden" name="lead_id" value="{{$quote->id}}">
                                 <select name="status" class="form-control js-select2-custom"
                                 onchange="document.getElementById('status-form{{$quote->id}}').submit()">
                                 <option value="New" {{ ($quote->status == 'New') ?'selected':''}}>New</option>
                                 <option value="Accepted" {{ ($quote->status == 'Accepted') ?'selected':''}} >Accepted</option>  
                                 <option value="Declined" {{ ($quote->status == 'Declined') ?'selected':''}}>Declined</option>
                                 </select>
                             </form>
                            </td>
                            <td>
                           <span class="d-block font-size-sm text-body">
                                     {{$quote->q_date == NULL ? '-' : $quote->q_date; }}
                                </span>
                            </td>
                         
                            <td>
                                <div class="btn--container justify-content-center">
                                    <a style="min-width:50px;" class="btn  btn--warning btn-outline-warning"
                                            href="{{ route('admin.quotation.manage', [$quote->id]) }}"
                                            title="{{ translate('messages.view') }}"><i class="tio-edit"></i>
                                        </a>
                              
                                    <a style="min-width:50px;" class="btn  btn--danger btn-outline-danger" href="{{ route('admin.quotation.delete', [$quote->id]) }}"
                                    title="{{translate('messages.delete')}} Quote"><i class="tio-delete-outlined"></i>
                                    </a>
                                  
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @if(count($quotes))
                <hr>
                 {!! $quotes->links() !!}
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