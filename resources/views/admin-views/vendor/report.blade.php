@extends('layouts.admin.app')

@section('title',translate('messages.Stores Report'))

@push('css_or_js')
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title">

            <span>
                Stores Report
            </span>
        </h1>
    </div>
    <!-- End Page Header -->
    <div class="card mt-3">
        <div class="card-header py-2 border-0">
            <div class="search--button-wrapper">
                <h5 class="card-title">Stores Report<span class="badge badge-soft-dark ml-2" id="itemCount"></span></h5>
                   
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
                            <th class="border-0">{{translate('sl')}}</th>
                            <th class="border-0">{{translate('messages.name')}}</th>
                            <th class="border-0">Updated By</th>
                            <th class="border-0">Status</th>
                            <th class="border-0">Reason</th>
                            <th class="border-0">Updated At</th>
                        </tr>
                    </thead>

                    <tbody id="table-div">
                        @foreach($stores as $key=>$store)
                        <tr>
                            <td>{{$key+1}}</td>
                            <td>
                                <div>
                                    <a href="{{route('admin.store.view', $store->id)}}" class="table-rest-info" alt="view store">
                                        <img class="img--60 circle onerror-image" data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                $store['logo'] ?? '',
                                                asset('storage/app/public/store').'/'.$store['logo'] ?? '',
                                                asset('public/assets/admin/img/160x160/img1.jpg'),
                                                'store/'
                                            ) }}">
                                        <div class="info">
                                            <div title="{{ $store?->name }}" class="text--title">
                                                {{Str::limit($store->name,20,'...')}}
                                            </div>
                                            <div class="font-light">
                                                {{translate('messages.id')}}:{{$store->id}}
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </td>

                            <td>{{ _getAdminName($store->status_updated_by)}}</td>
                            <td>
                                @if($store->vendor)
                                @if($store->vendor->status)
                                <span class="text-success">Approved</span>
                                @else
                                <span class="text-danger">Denied</span>
                                @endif
                                @else 
                                -
                                @endif
                            </td>
                            <td>{{$store->reason ? $store->reason : '-'}}</td>
                            <td>{{$store->updated_at}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
     

    </div>

</div>

@endsection

@push('script_2')
@endpush