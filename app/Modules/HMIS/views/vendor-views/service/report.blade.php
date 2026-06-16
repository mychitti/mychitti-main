@extends('layouts.vendor.app')
@section('title', 'Report')
@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        @include('hmis::vendor.hospital._hospital_submenu_header')
        <div class="page-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h1 class="page-header-title mb-2">
                    <span class="page-header-icon">
                        <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                    </span>
                    <span>
                        Report
                        <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count($staff) }}</span>
                    </span>

                </h1> 

            </div>
        </div>
        @if (hasPermission('leads_manage', 'report'))

        <!-- Page Heading -->
        <div class="card">

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="datatable"
                        class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                        data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false
                        }'>
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('messages.#') }}</th>
                                <th class="border-0">{{ translate('messages.name') }}</th>
                                <th class="border-0">{{ translate('messages.email') }}</th>
                                <th class="border-0">{{ translate('messages.phone') }}</th>
                                <th class="border-0">{{ translate('messages.Role') }}</th>
                                <th class="border-0">Services</th>
                                <th class="border-0 text-center">{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>
                        <tbody id="set-rows">
                            <tr>
                                <th scope="row">1</th>
                                <td class="text-capitalize text-break">Self</td>
                                <td>
                                    {{ \App\CentralLogics\Helpers::get_vendor_data()->email }}
                                </td>
                                <td>{{ \App\CentralLogics\Helpers::get_vendor_data()->phone }}</td>
                                <td>Vendor</td>
                                <td>{{ $self_done_services }}</td>
                                <td>
                                        <div class="btn--container justify-content-center">
                                            @if (hasPermission('leads_manage', 'list'))
                                                <a class="btn action-btn btn--primary btn-outline-primary"
                                                    href="{{ route('vendor.service.report.staff', [0]) }}"
                                                    title="View"><i class="tio-visible"></i>
                                                </a>
                                            @endif

                                        </div>
                                    </td>
                            </tr>
                            @foreach ($staff as $k => $e)
                                <tr>
                                    <th scope="row">{{ $k + 2 }}</th>
                                    <td class="text-capitalize text-break">{{ $e['f_name'] }} {{ $e['l_name'] }}
                                    </td>
                                    <td>
                                        {{ $e['email'] }}
                                    </td>
                                    <td>{{ $e['phone'] }}</td>
                                    <td>{{ $e->role ? $e->role['name'] : translate('messages.role_deleted') }}</td>
                                    <td>{{ $e->service_done }}</td>
                                    <td>
                                        <div class="btn--container justify-content-center">
                                            @if (hasPermission('leads_manage', 'list'))
                                                <a class="btn action-btn btn--primary btn-outline-primary"
                                                    href="{{ route('vendor.service.report.staff', [$e['id']]) }}"
                                                    title="View"><i class="tio-visible"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if (count($staff) === 0)
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
@endsection
