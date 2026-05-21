@extends('layouts.vendor.app')
@section('title', 'Report')
@push('css_or_js')
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
                        Report

                    </span>

                </h1>

            </div>
        </div>
        <!-- Page Heading -->

        <div class="card">

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="datatable"
                        class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('messages.#') }}</th>
                                <th class="border-0">{{ translate('messages.service') }}</th>
                                <th class="border-0">Assigned at</th>
                                <th class="border-0">Confirmed at</th>
                                <th class="border-0">Status</th>
                                <th class="border-0">Action</th>
                            </tr>
                        </thead>
                        <tbody id="set-rows">
                            @if (!$staff)
                                @foreach ($assigned_services as $k => $e)
                                    <tr>
                                        <th scope="row">{{ $k + 1 }}</th>
                                        <td>
                                            <img class="avatar avatar-lg mr-3 onerror-image"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($e->image, asset('storage/app/public/product/') . '/' . $e->image, asset('public/assets/admin/img/160x160/img2.jpg'), 'product/') }}"
                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                alt="{{ $e->name }} image">
                                            <div class="media-body">
                                                <h5 class="text-hover-primary mb-0">{{ $e->name }}</h5>
                                            </div>
                                        </td>
                                        <td>{{ $e->assigned_at }}</td>
                                        <td>{{ $e->confirmed_at }}</td>
                                        <td>{{ $e->job_status }}</td>
                                        <td>
                                            @if (hasPermission('leads_manage', 'view'))
                                                <a href="{{ route('vendor.service.lead-details', [$e->service_request_id]) }}"
                                                    class="btn action-btn btn--warning btn-outline-warning"
                                                    title="{{ translate('messages.view') }}"><i
                                                        class="tio-visible-outlined"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                @foreach ($staff->assigned_services as $k => $e)
                                    <tr>
                                        <th scope="row">{{ $k + 1 }}</th>
                                        <td>
                                            <img class="avatar avatar-lg mr-3 onerror-image"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($e->image, asset('storage/app/public/product/') . '/' . $e->image, asset('public/assets/admin/img/160x160/img2.jpg'), 'product/') }}"
                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                alt="{{ $e->name }} image">
                                            <div class="media-body">
                                                <h5 class="text-hover-primary mb-0">{{ $e->name }}</h5>
                                            </div>
                                        </td>
                                        <td>{{ $e->assigned_at }}</td>
                                        <td>{{ $e->confirmed_at }}</td>
                                        <td>{{ $e->job_status }}</td>
                                        <td>
                                            @if (hasPermission('leads_manage', 'view'))
                                                <a href="{{ route('vendor.service.lead-details', [$e->service_request_id]) }}"
                                                    class="btn action-btn btn--warning btn-outline-warning"
                                                    title="{{ translate('messages.view') }}"><i
                                                        class="tio-visible-outlined"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
