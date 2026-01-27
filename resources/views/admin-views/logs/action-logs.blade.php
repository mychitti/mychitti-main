@extends('layouts.admin.app')

@section('title', 'Action Logs')

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
                    {{ translate('messages.Action Logs') }}
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
                                <th class="border-0">{{ translate('messages.data_info') }}</th>
                                <th class="border-0">{{ translate('messages.description') }}</th>
                                <th class="border-0">{{ translate('messages.create_at') }}</th>
                            </tr>
                        </thead>

                        <tbody id="table-div">
                            @foreach ($actions as $key => $action)
                                <tr>
                                    <td class="pl-4">{{ $key + $actions->firstItem() }}</td>
                                    <td>{{ $action->admin?->f_name . ' ' . $action->admin?->l_name }}</td>
                                    <td>
                                        {{ $action['action'] }}
                                    </td>
                                    <td>
                                        {{ class_basename($action->model_type) }} ID: {{ $action->model_id }} <br>
                                        {{ ucfirst($action->data?->name) ?? '[Deleted]' }}
                                    </td>
                                    <td>
                                        <span class="d-block font-size-sm text-body text-capitalize">
                                            {{ Str::limit(translate($action['description']), 100, '...') }}
                                        </span>
                                    </td>
                                    <td>{{ $action->created_at }}</td>
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


    <div class="modal fade" id="warning-status-modal">
        <div class="modal-dialog modal-lg warning-status-modal">
            <div class="modal-content">
                <div class="modal-header pb-0">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true" class="tio-clear"></span>
                    </button>
                </div>
                <div class="single-item-slider owl-carousel">
                    <div class="item">
                        <div class="modal-header pt-0">
                            <h2 class="modal-title">{{ translate('How does it works ?') }}</h2>
                        </div>
                        <div class="modal-body">
                            <div class="how-it-works">
                                <div class="item">
                                    <img src="{{ asset('/public/assets/admin/img/how/how1.png') }}"
                                        class="h-60px object-contain object-left" alt="">
                                    <h2 class="serial">{{ translate('1') }}</h2>
                                    <h5>{{ translate('Create_Business_Module') }}</h5>
                                    <p>
                                        {{ translate('To_create_a_new_business_module,_go_to:_‘Module_Setup’_→_‘Add_Business_Module.’') }}
                                    </p>
                                </div>
                                <div class="item">
                                    <img src="{{ asset('/public/assets/admin/img/how/how2.png') }}"
                                        class="h-60px object-contain object-left" alt="">
                                    <h2 class="serial">{{ translate('2') }}</h2>
                                    <h5>{{ translate('Add_Module_to_Zone') }}</h5>
                                    <p>
                                        {{ translate('Go_to_‘Zone_Setup’→_‘Business_Zone_List’→_‘Zone_Settings’→_Choose_Payment_Method→Add_Business_Module_into_Zone_with_Parameters.') }}
                                    </p>
                                </div>
                                <div class="item mw-100">
                                    <img src="{{ asset('/public/assets/admin/img/how/how3.png') }}"
                                        class="h-60px object-contain object-left" alt="">
                                    <h2 class="serial">{{ translate('3') }}</h2>
                                    <h5>{{ translate('Create_Stores') }}</h5>
                                    <p>
                                        {{ translate('Select_your_Module_from_the_Module_Section,_Click_→_’Store_Management’→’Add_Store’→Add_Store_details_&_select_Zone_to_integrate_Module+Zone+Store.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="item">
                        <div class="modal-body py-0">
                            <div class="text-center ">
                                <h3 class="modal-title mb-3">
                                    {{ translate('Please go to settings and select module for this zone') }}</h3>
                                <p class="txt">
                                    {{ translate("Otherwise this zone won't function properly & will work show anything against this zone") }}
                                </p>
                            </div>
                            <img src="{{ asset('/public/assets/admin/img/zone-settings-popup-arrow.gif') }}"
                                alt="admin/img" class="w-100 h-unset">
                        </div>
                    </div>
                    <div class="item px-xl-4">
                        <div class="d-flex align-items-center">
                            <div class="col-sm-4 text-14">
                                <h4>{{ translate('Make Sure') }}</h4>
                                <p>
                                    {{ translate('All of your module details should be well-structured. Because those details are dynamically shown on the Landing page of your business.') }}
                                </p>
                            </div>
                            <div class="col-sm-8">
                                <img src="{{ asset('/public/assets/admin/img/module2.png') }}" alt="admin/img"
                                    class="w-100 h-unset">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-center pb-5">
                    <div class="slide-counter"></div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <script></script>
@endpush
