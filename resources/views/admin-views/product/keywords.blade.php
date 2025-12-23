@extends('layouts.admin.app')

@section('title',translate('Keywords Manage'))

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <!-- <div class="page-header">
        <h1 class="page-header-title"><i class="tio-filter-list"></i>Service Keywords<span class="badge badge-soft-dark ml-2" id="itemCount">{{count($keywords)}}</span></h1>
        <div class="page-header-select-wrapper">


        </div>
    </div> -->
    <!-- End Page Header -->


    <div class="row d-none">
        <!-- Card -->
        <div class="card col-6">
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Add Service Keyword</h5>

                </div>
            </div>
            <form class="w-100" action="{{ route('admin.item.keyword-save') }}" method="post">
                @csrf
                <input type="hidden" id="lead_id" name="lead_id" value="">
                <div class="col-md-12">
                    <div class="card h-100">
                        <div class="card-body row">
                            <div class="form-row col-12">
                                <input type="text" name="keyword" placeholder="Keyword" class="form-control">
                            </div>


                            <div class="form-row d-flex align-items-end col-12 mt-2">
                                <button class="btn btn--primary ">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card col-6">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Service Keyword List</h5>

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
                            <th class="border-0">Keyword</th>

                            <th class="text-center border-0">{{translate('messages.action')}}</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach($keywords as $lead)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div>
                                    <a href="" class="table-rest-info" alt="view store">

                                        <div class="info">
                                            <div class="text--title">
                                                {{$lead->keyword}}
                                            </div>

                                        </div>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="btn--container justify-content-center">
                                    <a style="min-width:50px;" class="btn  btn--danger btn-outline-danger" href="{{ route('admin.item.delete-keyword', [$lead->id]) }}"
                                        title="{{translate('messages.delete')}} {{translate('messages.store')}}"><i class="tio-delete-outlined"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @if(count($keywords))
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
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title"><i class="tio-filter-list"></i>Location Keywords<span class="badge badge-soft-dark ml-2" id="itemCount">{{count($keywords)}}</span></h1>
        <div class="page-header-select-wrapper">


        </div>
    </div>
    <!-- End Page Header -->


    <div class="row">
        <!-- Card -->
        <div class="card col-6">
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Add Location Keyword</h5>

                </div>
            </div>
            <form class="w-100" action="{{ route('admin.item.location-keyword-save') }}" method="post">
                @csrf
                <input type="hidden" id="lead_id" name="lead_id" value="">
                <div class="col-md-12">
                    <div class="card h-100">
                        <div class="card-body row">
                            <div class="form-row col-12">
                                <input type="text" name="keyword" placeholder="Keyword" class="form-control">
                            </div>


                            <div class="form-row d-flex align-items-end col-12 mt-2">
                                <button class="btn btn--primary ">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card col-6">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Location Keyword List</h5>

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
                            <th class="border-0">Keyword</th>

                            <th class="text-center border-0">{{translate('messages.action')}}</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach($locationKeywords as $lead)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div>
                                    <a href="" class="table-rest-info" alt="view store">

                                        <div class="info">
                                            <div class="text--title">
                                                {{$lead->keyword}}
                                            </div>

                                        </div>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="btn--container justify-content-center">
                                    <a style="min-width:50px;" class="btn  btn--danger btn-outline-danger" href="{{ route('admin.item.delete-location-keyword', [$lead->id]) }}"
                                        title="{{translate('messages.delete')}} {{translate('messages.store')}}"><i class="tio-delete-outlined"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @if(count($keywords))
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
                location.href = url;
            }
        })
    }
    $(document).on('ready', function() {
        // INITIALIZATION OF DATATABLES
        // =======================================================
        var datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

        $('#column1_search').on('keyup', function() {
            datatable
                .columns(1)
                .search(this.value)
                .draw();
        });

        $('#column2_search').on('keyup', function() {
            datatable
                .columns(2)
                .search(this.value)
                .draw();
        });

        $('#column3_search').on('keyup', function() {
            datatable
                .columns(3)
                .search(this.value)
                .draw();
        });

        $('#column4_search').on('keyup', function() {
            datatable
                .columns(4)
                .search(this.value)
                .draw();
        });


        // INITIALIZATION OF SELECT2
        // =======================================================
        $('.js-select2-custom').each(function() {
            var select2 = $.HSCore.components.HSSelect2.init($(this));
        });
    });
</script>

@endpush