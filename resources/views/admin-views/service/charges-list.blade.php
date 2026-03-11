@extends('layouts.admin.app')

@section('title',translate('Charges List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Lead Charges<span class="badge badge-soft-dark ml-2" id="itemCount">{{count($charges)}}</span></h1>
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


<div class="row">
        <!-- Card -->
        <div class="card col-12">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Lead Charges</h5>
                    <!--<form action="javascript:" id="search-form" class="search-form">-->
                                  
                    <!--    @csrf-->
                    <!--    <div class="input-group input--group">-->
                    <!--        <input id="datatableSearch_" type="search" name="search" class="form-control"-->
                    <!--                placeholder="Search Status" aria-label="{{translate('messages.search')}}" required>-->
                    <!--        <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>-->

                    <!--    </div>-->
                        <!-- End Search -->
                    <!--</form>-->
                 
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
                        <th class="border-0">Category</th>
                        <th class="border-0">Service</th>
                        <th class="border-0">Zone</th>
                        <th class="border-0">Charges</th>
                        <th class="text-center border-0">{{translate('messages.action')}}</th>
                    </tr>
                    </thead>
<!--currency_code()-->
                    <tbody id="set-rows">
                   @foreach($charges as $lead)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{$lead->cat_name}}</td>
                            <td>{{ $lead->item_name ?? 'All Services' }}</td>
                            <td>{{$lead->zone_name}}</td>
                            <td>
                                <div class="info">
                                    <div class="text--title">
                                    <b>1st : </b>{{\App\CentralLogics\Helpers::currency_symbol() . $lead->ven_1_charges}}, &nbsp;
                                    <b>2nd : </b>{{\App\CentralLogics\Helpers::currency_symbol() . $lead->ven_2_charges}}, &nbsp;
                                    <b>3rd : </b>{{\App\CentralLogics\Helpers::currency_symbol() . $lead->ven_3_charges}}, &nbsp;
                                    <b>Others :</b>{{\App\CentralLogics\Helpers::currency_symbol() . $lead->ven_other_charges}}, &nbsp;
                                    <b>Confirmation :</b>{{\App\CentralLogics\Helpers::currency_symbol() . ($lead->confirmation_charge ?? 0)}}
                                    </div>
                                </div>
                            </td>
                            <td>
                            
                        
                                <div class="btn--container justify-content-center">
                                    
                                     <a style="min-width:50px;" class="btn  btn--primary btn-outline-primary" href="{{ route('admin.service.edit-charges', [$lead->id]) }}"
                                    title="Edit Charges"><i class="tio-edit"></i>
                                    </a>
                                    
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
              @if(count($charges))
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