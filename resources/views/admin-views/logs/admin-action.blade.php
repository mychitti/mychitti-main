@extends('layouts.admin.app')

@section('title', 'Admin Action')

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
    @include('admin-views/partials/_action-nav')
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/module.png') }}" alt="">
                </span>
                <span>
                    {{ translate('messages.Admin Actions') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="card">
            <!-- Header -->

            <!-- End Header -->
            <div class="card-body p-0">
                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable" class="table table-borderless table-thead-bordered table-align-middle"
                        data-hs-datatables-options='{
                            "isResponsive": false,
                            "isShowPaging": false,
                            "paging":false,
                        }'>
                        <thead class="thead-light border-0">
                            <tr>
                                <th class="border-0">{{ translate('messages.sl') }}</th>
                                <th class="border-0">{{ translate('messages.admin') }}</th>
                                <th class="border-0">{{ translate('messages.action') }}</th>
                                <th class="border-0">{{ translate('messages.status') }}</th>
                                <th class="border-0">{{ translate('messages.create_at') }}</th>
                            </tr>
                        </thead>

                        <tbody id="table-div">
                            @foreach ($actions as $key => $action)
                                <tr>
                                    <td class="pl-4">{{ $key + $actions->firstItem() }}</td>
                                    <td>{{ $action->admin?->f_name . ' ' . $action->admin?->l_name }}</td>
                                  
                                    <td>
                                        {{ ucfirst($action->action_type) }}
                                    </td>
                                    <td>
                                    @if($action->status == 'pending')
                                    <span class="badge badge-soft-warning">Pending</span>
                                    @else
                                    <span class="badge badge-soft-success">Verified</span>
                                    @endif
                                        </td>
                                    <td>{{$action->created_at}}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer page-area pt-0 border-0">
                <!-- Pagination -->
                <div class="d-flex justify-content-center justify-content-sm-end">
                    <!-- Pagination -->
                    {!! $actions->links() !!}
                </div>
                <!-- End Pagination -->
                @if (count($actions) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif
            </div>
        </div>
    </div>


@endsection

@push('script_2')
    <script></script>
@endpush
