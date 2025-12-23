@extends('layouts.admin.app')

@section('title', 'Status Requests')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> New Status Requests</h1>
            <div class="page-header-select-wrapper">


            </div>
        </div>
        <!-- End Page Header -->


        <div class="row">
            <!-- Card -->
            <div class="card col-12">
                <!-- Header -->
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
                                    <th class="border-0">{{ translate('messages.id') }}</th>
                                    <th class="border-0 ">{{ translate('messages.store_info') }}</th>
                                    <th class="border-0 ">{{ translate('messages.status') }}</th>
                                    <th class="border-0 ">{{ translate('messages.action') }}</th>
                                </tr>
                            </thead>

                            <tbody id="table-div">
                                @foreach ($stts_requests as $key => $stts)
                                    <tr>
                                        <td>{{ $key + $stts_requests->firstItem() }}</td>
                                        <td>{{ $stts->id }}</td>
                                        <td>
                                            <div>
                                                <a href="{{ route('admin.store.view', $stts->store?->id) }}"
                                                    class="table-rest-info" alt="view store">
                                                    <img class="img--60 circle onerror-image"
                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                            $stts->store['logo'] ?? '',
                                                            asset('storage/app/public/store') . '/' . $stts->store['logo'] ?? '',
                                                            asset('public/assets/admin/img/160x160/img1.jpg'),
                                                            'store/',
                                                        ) }}">
                                                    <div class="info">
                                                        <div title="{{ $stts->store?->name }}" class="text--title">
                                                            {{ Str::limit($stts->store?->name, 20, '...') }}
                                                        </div>
                                                        <div class="font-light">
                                                            {{ translate('messages.id') }}:{{ $stts->store?->id }}
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="d-block font-size-sm text-body">
                                                {{ $stts->serviceStatus['status'] }}
                                            </span>
                                        </td>

                                        <td>
                                            <a style="width: fit-content; padding: 0px 10px !important;" class="btn action-btn btn--primary btn-outline-primary"
                                                href="{{ route('admin.service.approve-status-request', [$stts['id']]) }}"
                                                title="{{ translate('messages.edit_category') }}"><i
                                                    class="tio-checkmark-square-outlined"></i> Approve
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if (count($stts_requests) !== 0)
                    <hr>
                @endif
                <div class="page-area">
                    {!! $stts_requests->appends($_GET)->links() !!}
                </div>
                @if (count($stts_requests) === 0)
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
