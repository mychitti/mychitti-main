@extends('layouts.admin.app')

@section('title', 'Service Trash')

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/module.png') }}" alt="">
                </span>
                <span>
                    {{ translate('messages.Service Trash') }}
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
                                <th class="border-0">{{ translate('messages.info') }}</th>
                                <th class="border-0">{{ translate('messages.deleted_by') }}</th>
                                <th class="border-0">{{ translate('messages.deleted_at') }}</th>
                                <th class="border-0">{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>

                        <tbody id="table-div">
                            @foreach ($items as $key => $dt)
                                <tr>
                                    <td class="pl-4">{{ $key + $items->firstItem() }}</td>
                                    <td>
                                    <a class="media align-items-center"
                                        href="#">
                                        <img class="avatar avatar-lg mr-3 onerror-image"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                $dt['image'] ?? '',
                                                asset('storage/app/public/product') . '/' . $dt['image'] ?? '',
                                                asset('public/assets/admin/img/160x160/img2.jpg'),
                                                'product/',
                                            ) }}"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                            alt="{{ $dt->name }} image">
                                        <div title="{{ $dt['name'] }}" class="media-body">
                                            <h5 class="text-hover-primary mb-0">{{ Str::limit($dt['name'], 20, '...') }}
                                            </h5>
                                        </div>
                                    </a>
                                    </td>
                                    <td>
                                       {{ $dt->deleted_by ? _getAdminName($dt->deleted_by) : ''}}
                                    </td>
                                    <td>
                                       {{ $dt->deleted_at}}
                                    </td>
                                    <td>
                                    <a href="{{route('admin.item.trash.delete-item', [$dt->id])}}" class="btn btn-outline-danger"><i class="tio-delete"></i> Delete Permanently</a>
                                    <a href="{{route('admin.item.trash.restore-item', [$dt->id])}}" class="btn btn-success"><i class="tio-restore"></i> Restore</a>
                                    <a href="{{route('admin.item.trash.view-item', [$dt->id])}}" class="btn btn-outline-info"><i class="tio-visible"></i></a>

                                    </td> 
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
                    {!! $items->links() !!}
                </div>
                <!-- End Pagination -->
                @if (count($items) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif
            </div>
        </div>
        <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/module.png') }}" alt="">
                </span>
                <span>
                    {{ translate('messages.Category Trash') }}
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
                                <th class="border-0">{{ translate('messages.info') }}</th>
                                <th class="border-0">{{ translate('messages.deleted_by') }}</th>
                                <th class="border-0">{{ translate('messages.deleted_at') }}</th>
                                <th class="border-0">{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>

                        <tbody id="table-div">
                            @foreach ($categories as $key => $dt)
                                <tr>
                                    <td class="pl-4">{{ $key + $categories->firstItem() }}</td>
                                    <td>
                                    <a class="media align-items-center"
                                        href="#">
                                        <img class="avatar avatar-lg mr-3 onerror-image"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                $dt['image'] ?? '',
                                                asset('storage/app/public/product') . '/' . $dt['image'] ?? '',
                                                asset('public/assets/admin/img/160x160/img2.jpg'),
                                                'product/',
                                            ) }}"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                            alt="{{ $dt->name }} image">
                                        <div title="{{ $dt['name'] }}" class="media-body">
                                            <h5 class="text-hover-primary mb-0">{{ Str::limit($dt['name'], 20, '...') }}
                                            </h5>
                                        </div>
                                    </a>
                                    </td>
                                    <td>
                                       {{ $dt->deleted_by ? _getAdminName($dt->deleted_by) : ''}}
                                    </td>
                                    <td>
                                       {{ $dt->deleted_at}}
                                    </td>
                                     <td>
                                    <a href="{{route('admin.item.trash.delete-category', [$dt->id])}}" class="btn btn-outline-danger"><i class="tio-delete"></i> Delete Permanently</a>
                                    <a href="{{route('admin.item.trash.restore-category', [$dt->id])}}" class="btn btn-success"><i class="tio-restore"></i> Restore</a>
                                    <a href="{{route('admin.item.trash.view-category', [$dt->id])}}" class="btn btn-outline-info"><i class="tio-visible"></i></a>
                                    </td> 
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
                    {!! $categories->links() !!}
                </div>
                <!-- End Pagination -->
                @if (count($categories) === 0)
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
